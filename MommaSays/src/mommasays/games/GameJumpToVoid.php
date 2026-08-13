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

use pocketmine\event\player\PlayerMoveEvent;

class GameJumpToVoid extends Game
{
    public const VOID_TRESHOLD = 38;

    public function getMessage(): string
    {
        return 'Jump to the void';
    }

    public function onMoveEvent(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();

        if ($event->getTo()->getY() <= self::VOID_TRESHOLD && !$this->isWinner($player->getName())) {
            $this->addWinner($player);
            $event->cancel();
        }
    }

    public function isUsingCages(): bool
    {
        return true;
    }
}