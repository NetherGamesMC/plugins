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

namespace murdermystery\utils;

use murdermystery\MurderMystery;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class Utils extends \libminigames\utils\Utils
{
    /**
     * @param Player $player
     * @param string $soundName
     * @param float $volume
     * @param float $pitch
     *
     * @deprecated
     */
    public static function playSound(Player $player, string $soundName, float $volume = 0.0, float $pitch = 0.0): void
    {
        /** @var NGPlayer $player */
        $player->playSound($soundName, $volume, $pitch);
    }

    public static function spawnPlayerHuman(Player $player): void
    {
        $npc = new MMPlayer($player);
        $npc->spawnToAll();

        MurderMystery::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($npc): void {
            if (!$npc->isClosed()) {
                $npc->playAnimation();
            }
        }), 5);
    }
}