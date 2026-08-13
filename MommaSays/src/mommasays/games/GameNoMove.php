<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\games;

use mommasays\MommaSays;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\scheduler\ClosureTask;

class GameNoMove extends Game
{
    /** @var bool */
    public bool $checkForMovement = false;

    public function getMessage(): string
    {
        return "Stand completely still";
    }

    public function setupArena(): void
    {
        MommaSays::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () {
            $this->checkForMovement = true;
        }), 20);
    }

    public function onMoveEvent(PlayerMoveEvent $event): void
    {
        if ($this->checkForMovement && !$this->isLoser($event->getPlayer()->getName()) && !$event->getTo()->asVector3()->equals($event->getFrom()->asVector3())) {
            $this->addLoser($event->getPlayer());
        }
    }

    public function finishGame(): void
    {
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            if (!$this->isLoser($player->getName())) {
                $this->addWinner($player);
            }
        }
    }

    public function isUsingCages(): bool
    {
        return true;
    }
}