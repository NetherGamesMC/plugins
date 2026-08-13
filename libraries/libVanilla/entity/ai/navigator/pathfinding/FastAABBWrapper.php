<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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


namespace libVanilla\entity\ai\navigator\pathfinding;

use Generator;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class FastAABBWrapper
{

    /** @var Vector3[] */
    private readonly array $checkQueue;

    public static function centerAABB(AxisAlignedBB $unCenteredAABB): AxisAlignedBB
    {
        [$xl, $zl, $yl] = [
            abs($unCenteredAABB->getXLength() / 2),
            abs($unCenteredAABB->getZLength() / 2),
            abs($unCenteredAABB->getYLength())
        ];

        return new AxisAlignedBB(-$xl, 0, -$zl, $xl, $yl, $zl);
    }

    public static function fromEntityAABB(AxisAlignedBB $unCenteredAABB): self
    {
        return new self(self::centerAABB($unCenteredAABB));
    }

    public function __construct(protected AxisAlignedBB $aaBB)
    {
        $checkQueue = [];
        $minX = (int)floor($this->aaBB->minX - 1);
        $minY = (int)floor($this->aaBB->minY - 1);
        $minZ = (int)floor($this->aaBB->minZ - 1);
        $maxX = (int)floor($this->aaBB->maxX + 1);
        $maxY = (int)floor($this->aaBB->maxY + 1);
        $maxZ = (int)floor($this->aaBB->maxZ + 1);

        for ($z = $minZ; $z <= $maxZ; ++$z) {
            for ($x = $minX; $x <= $maxX; ++$x) {
                for ($y = $minY; $y <= $maxY; ++$y) {
                    $checkQueue[] = [($pos = new Vector3($x, $y, $z)), $pos->lengthSquared()];
                }
            }
        }
        usort($checkQueue, fn(array $a, array $b) => $b[1] <=> $a[1]);
        $this->checkQueue = array_map(fn(array $a) => $a[0], $checkQueue);
    }

    public function getCollisionBoxes(World $world, Vector3 $origin, int $limit = -1): Generator
    {
        $count = $limit >= 0 ? 0 : -INF;
        $offsetAABB = $this->aaBB->offsetCopy($origin->x, $origin->y, $origin->z);

        foreach ($this->checkQueue as $pos) {
            if (++$count >= $limit) {
                return;
            }
            $pos = $pos->addVector($origin)->floor();

            $aaBBs = ($block = $world->getBlockAt($pos->x, $pos->y, $pos->z))->getCollisionBoxes();
            if (count($aaBBs) < 1) {
                continue;
            }
            foreach ($aaBBs as $blockBB) {
                if (!$blockBB->intersectsWith($offsetAABB)) {
                    continue;
                }
                yield $block => $blockBB;
            }
        }
    }

    public function hasCollision(World $world, Vector3 $origin): bool
    {
        return $this->getCollisionBoxes($world, $origin)->valid();
    }
}