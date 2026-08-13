<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars\utils;

use bedwars\Bedwars;
use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class Sponge
{
    public static function absorbWater(Bedwars $plugin, Position $pos, \pocketmine\block\Sponge $sponge): void
    {
        if ($sponge->isWet()) {
            return;
        }

        $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($pos): void {
            if ($pos->isValid()) {
                $pos->getWorld()->setBlock($pos, VanillaBlocks::AIR());
            }
        }), 2 * 20);

        $world = $pos->getWorld();
        $yBlock = $pos->getFloorY();
        $zBlock = $pos->getFloorZ();
        $xBlock = $pos->getFloorX();

        $radius = 7;
        $touchingWater = false;

        for ($x = -1; $x <= 1; ++$x) {
            for ($y = -1; $y <= 1; ++$y) {
                for ($z = -1; $z <= 1; ++$z) {
                    $id = $world->getBlockAt($xBlock + $x, $yBlock + $y, $zBlock + $z)->getTypeId();
                    if ($id === BlockTypeIds::WATER) {
                        $touchingWater = true;
                    }
                }
            }
        }

        if ($touchingWater) {
            $selection = new Selection();
            $air = VanillaBlocks::AIR();

            for ($x = $pos->getFloorX() - $radius; $x <= $pos->getFloorX() + $radius; $x++) {
                $xsqr = ($pos->getFloorX() - $x) * ($pos->getFloorX() - $x);
                for ($y = $pos->getFloorY() - $radius; $y <= $pos->getFloorY() + $radius; $y++) {
                    $ysqr = ($pos->getFloorY() - $y) * ($pos->getFloorY() - $y);
                    for ($z = $pos->getFloorZ() - $radius; $z <= $pos->getFloorZ() + $radius; $z++) {
                        $zsqr = ($pos->getZ() - $z) * ($pos->getZ() - $z);
                        if ((($xsqr + $ysqr + $zsqr) <= ($radius * $radius)) && $y > 0) {
                            $id = $world->getBlockAt($x, $y, $z)->getTypeId();

                            if ($id === BlockTypeIds::WATER) {
                                $selection->add($x, $y, $z, $air);
                            }
                        }
                    }
                }
            }

            $sponge->setWet(true);
            AsyncBlockManager::executeSet($selection, $world);
        }
    }
}