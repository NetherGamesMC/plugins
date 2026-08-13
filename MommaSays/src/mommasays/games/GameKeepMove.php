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

use NetherGames\NGEssentials\player\NGPlayer;

class GameKeepMove extends Game
{
    public const MOVE_TRESHOLD = 1;
    /** @var float */
    private float $tickTime = 0;

    public function getMessage(): string
    {
        return "Don't stop moving";
    }

    public function tickMovementCheck(): void
    {
        /** @var NGPlayer $player */
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            if (!$this->isLoser($player->getName()) && ($this->tickTime - $player->getLastMoveTime()) > self::MOVE_TRESHOLD) {
                $this->addLoser($player);
            }
        }

        $this->tickTime = microtime(true);
    }

    public function finishGame(): void
    {
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            if (!$this->isLoser($player->getName())) {
                $this->addWinner($player);
            }
        }
    }
}
