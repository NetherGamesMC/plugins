<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars\utils;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use skywars\entities\SWPlayer;
use skywars\Skywars;

class Utils extends \libminigames\utils\Utils
{
    public static function randomString(int $length = 10): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function spawnPlayerHuman(Player $player): void
    {
        $npc = new SWPlayer($player);
        $npc->spawnToAll();

        Skywars::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($npc): void {
            if (!$npc->isClosed()) {
                $npc->playAnimation();
            }
        }), 5);
    }
}