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

use pocketmine\form\Form as IForm;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;

abstract class Form implements IForm
{
    /** @var string */
    private string $title = '';
    /** @var Player|null */
    private ?Player $player;

    public function __construct(?Player $player)
    {
        $this->player = $player;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function sendForm(): void
    {
        /** @var NGPlayer $player */
        $player = $this->getPlayer();

        if (FormManager::canSend($player)) {
            $player->sendForm(clone $this);

            FormManager::scheduleNetworkStackLatency($player);
            FormManager::sendLastForm($player);
        }
    }

    public function getPlayer(): Player
    {
        if ($this->player === null) {
            throw new PacketHandlingException('Tried to use player in a static form');
        }

        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->getTitle(),
        ];
    }
}