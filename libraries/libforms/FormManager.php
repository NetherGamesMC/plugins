<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms;

use Closure;
use libforms\elements\Button;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function count;
use function microtime;
use function min;
use function mt_rand;

class FormManager
{
    /** @var float[] */
    private static array $lastForm = [];
    /** @var Form[] */
    private static array $staticForms = [];
    /** @var PluginBase */
    private static PluginBase $plugin;

    public function __construct(PluginBase $plugin)
    {
        $plugin->getServer()->getPluginManager()->registerEvents(new EventListener(), $plugin);
        self::$plugin = $plugin;
    }

    public static function saveStaticForm(Form $form, int $id): void
    {
        if (isset(self::$staticForms[$id])) {
            throw new PacketHandlingException('Tried to overwrite static form');
        }

        self::$staticForms[$id] = $form;
    }

    public static function scheduleNetworkStackLatency(Player $player): void
    {
        self::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($player): void {
            if ($player->isOnline()) {
                $timestamp = mt_rand() * 1000;

                $player->getNetworkSession()->sendDataPacket(NetworkStackLatencyPacket::request($timestamp));

                EventListener::$timestampData[$player->getId()] = $timestamp;
            }
        }), 2);
    }

    public static function onQuit(Player $player): void
    {
        unset(self::$lastForm[$player->getId()]);
    }

    public static function createCustomForm(?Player $player = null, ?Closure $callable = null, ?Closure $onClose = null): ?CustomForm
    {
        if ($player === null || self::canSend($player)) {
            return new CustomForm($player, $callable, $onClose);
        }

        return null;
    }

    public static function canSend(Player $player): bool
    {
        return (self::$lastForm[$player->getId()] ?? 0.0) < microtime(true) - 0.2;
    }

    public static function sendLastForm(Player $player): void
    {
        self::$lastForm[$player->getId()] = microtime(true);
    }

    public static function createSimpleForm(?Player $player = null, ?Closure $onClose = null): ?SimpleForm
    {
        if ($player === null || self::canSend($player)) {
            return new SimpleForm($player, $onClose);
        }

        return null;
    }

    /**
     * @template T
     *
     * @param list<T> $array
     * @param Closure(SimpleForm, int, list<T>): void $onPageChange
     */
    public static function createSimplePaginatedForm(Player $player, array $array, Closure $onPageChange, int $pageSize, ?Closure $onClose = null): void
    {
        self::createSimplePageForm($player, $array, 0, $onPageChange, $pageSize, $onClose);
    }

    /**
     * @template T
     *
     * @param list<T> $array
     * @param Closure(SimpleForm, int, list<T>): void $onPageChange
     */
    private static function createSimplePageForm(Player $player, array $array, int $page, Closure $onPageChange, int $pageSize, ?Closure $onClose = null): void
    {
        $form = self::createSimpleForm($player, $onClose);

        if ($form !== null) {
            $start = $page * $pageSize;
            $onPageChange($form, $page, array_slice($array, $start, $pageSize));

            if ($page > 0) {
                $form->addButton(new Button(TextFormat::GREEN . TextFormat::BOLD . 'Previous Page', fn(Player $player) => self::createSimplePageForm($player, $array, $page - 1, $onPageChange, $pageSize, $onClose)));
            }

            if (count($array) > $start + $pageSize) {
                $form->addButton(new Button(TextFormat::GREEN . TextFormat::BOLD . 'Next Page', fn(Player $player) => self::createSimplePageForm($player, $array, $page + 1, $onPageChange, $pageSize, $onClose)));
            }

            $form->sendForm();
        }
    }

    public static function createModalForm(?Player $player = null, ?Closure $onClose = null): ?ModalForm
    {
        if ($player === null || self::canSend($player)) {
            return new ModalForm($player, $onClose);
        }

        return null;
    }

    public static function sendStaticForm(Player $player, int $id): void
    {
        $form = clone self::getStaticForm($id);
        $form->setPlayer($player);
        $form->sendForm();
    }

    private static function getStaticForm(int $id): Form
    {
        if (!isset(self::$staticForms[$id])) {
            throw new PacketHandlingException('static form ' . $id . " doesn't exist");
        }

        return self::$staticForms[$id];
    }
}