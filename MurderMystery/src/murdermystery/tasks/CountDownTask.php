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
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace murdermystery\tasks;

use libminigames\Arena;
use murdermystery\gamemodes\MMArena;

class CountDownTask extends \libminigames\tasks\CountDownTask
{
    public function onRun(): void
    {
        parent::onRun();

        $arena = $this->getArena();
        foreach ($arena->getAlivePlayers() as $player) {
            $player->sendPopup($arena->getWaitingPopup($player));
        }
    }

    /**
     * @return MMArena
     */
    public function getArena(): Arena
    {
        /** @var MMArena $arena */
        $arena = parent::getArena();

        return $arena;
    }
}