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

namespace mommasays\utils;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use function array_rand;

class Utils extends \libminigames\utils\Utils
{
    public static function getRandomBlock(): Block
    {
        $blocks = [
            VanillaBlocks::EMERALD_ORE(),
            VanillaBlocks::TNT(),
            VanillaBlocks::DIAMOND(),
            VanillaBlocks::REDSTONE(),
            VanillaBlocks::OAK_PLANKS(),
            VanillaBlocks::STONE_BRICKS(),
            VanillaBlocks::NETHER_BRICKS(),
            VanillaBlocks::BONE_BLOCK(),
            VanillaBlocks::HARDENED_CLAY(),
            VanillaBlocks::CONCRETE(),
            VanillaBlocks::CLAY()
        ];

        return $blocks[array_rand($blocks)];
    }
}