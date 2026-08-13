<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, Driesboy
 *
 */
declare(strict_types=1);

namespace murdermystery\utils;

use murdermystery\MurderMystery;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use function mt_rand;

class MMPlayer extends Human
{
    /** @var string */
    private string $animation;
    /** @var bool */
    private bool $send = false;

    public function __construct(Player $player)
    {
        parent::__construct($player->getLocation(), $player->getSkin());

        $this->animation = match (mt_rand(0, 2)) {
            0 => 'animation.ng.corpse.back1',
            1 => 'animation.ng.corpse.belly1',
            default => 'animation.ng.corpse.overkill'
        };

        $this->setCanSaveWithChunk(false);
    }

    public function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0, 0, 0);
    }

    public function playAnimation(): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::IDLING, false);

        $this->getWorld()->broadcastPacketToViewers($this->getPosition()->asVector3(), AnimateEntityPacket::create(
            $this->animation,
            "",
            "",
            0,
            "",
            0,
            [$this->getId()]
        ));
        $this->send = true;
    }

    public function sendSpawnPacket(Player $player): void
    {
        if ($this->send) {
            $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::IDLING, true);

            parent::sendSpawnPacket($player);

            MurderMystery::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                if ($player->isConnected()) {
                    $player->getNetworkSession()->sendDataPacket(AnimateEntityPacket::create(
                        $this->animation . '.end',
                        "",
                        "",
                        0,
                        "",
                        0,
                        [$this->getId()]
                    ));
                }
            }), 5);
        } else {
            parent::sendSpawnPacket($player);
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }
}