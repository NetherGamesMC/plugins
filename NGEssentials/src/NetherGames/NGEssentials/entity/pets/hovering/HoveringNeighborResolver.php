<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\hovering;

use Generator;
use libVanilla\entity\ai\navigator\pathfinding\neighbor\NeighborResolver;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class HoveringNeighborResolver implements NeighborResolver
{
    public function __construct(private AxisAlignedBB $holderAABB)
    {
        // normalize the AABB to 0,0,0 by removing the midpoint for X and Z
        $this->holderAABB = $holderAABB->offsetCopy(
            -($this->holderAABB->minX + $this->holderAABB->maxX) / 2,
            -$this->holderAABB->minY,
            -($this->holderAABB->minZ + $this->holderAABB->maxZ) / 2
        );
    }

    public function findNeighbors(World $world, Vector3 $current): Generator
    {
        $currentBlock = $world->getBlock($current);
        foreach (Facing::ALL as $sideIndex) {
            $neighborVec3 = $current->getSide($sideIndex);
            $neighborBlock = $currentBlock->getSide($sideIndex);

            if ($this->fastBlockCollisionCheck($world, $neighborBlock->getPosition()->add(0.5, 0.5, 0.5))) {
                continue;
            }

            /*if ($neighborBlock->isSolid()) {
                continue;
            }

            if ($sideIndex === Facing::UP || self::IS_HORIZONTAL[$sideIndex]) {
                for ($i = 1; $i <= $this->holderAABB->getYLength() + 1; $i++) {
                    $columnBlock = $neighborBlock->getSide(Facing::UP, $i);
                    if ($columnBlock->isSolid()) {
                        continue 2;
                    }
                }
            }*/

            yield $neighborVec3;
        }
    }

    public function fastBlockCollisionCheck(World $world, Vector3 $origin): bool
    {
        $aaBB = $this->holderAABB->offsetCopy($origin->x, $origin->y, $origin->z);

        $minX = (int)floor($aaBB->minX - 1);
        $minY = (int)floor($aaBB->minY - 1);
        $minZ = (int)floor($aaBB->minZ - 1);
        $maxX = (int)floor($aaBB->maxX + 1);
        $maxY = (int)floor($aaBB->maxY + 1);
        $maxZ = (int)floor($aaBB->maxZ + 1);

        for ($z = $minZ; $z <= $maxZ; ++$z) {
            for ($x = $minX; $x <= $maxX; ++$x) {
                for ($y = $minY; $y <= $maxY; ++$y) {
                    $block = $world->getBlockAt($x, $y, $z);
                    if ($block->isSolid() && $block->collidesWithBB($aaBB)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}