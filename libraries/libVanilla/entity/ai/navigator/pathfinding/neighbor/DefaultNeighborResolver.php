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

namespace libVanilla\entity\ai\navigator\pathfinding\neighbor;

use Generator;
use libVanilla\entity\ai\navigator\pathfinding\cache\PathFindingNeighborCacheManager;
use libVanilla\entity\ai\navigator\pathfinding\FastAABBWrapper;
use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\block\utils\SlabType;
use pocketmine\entity\Entity;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class DefaultNeighborResolver implements NeighborResolver
{
    // we're assuming faster lookups instead of in_array
    private const IS_HORIZONTAL = [
        Facing::NORTH => true,
        Facing::SOUTH => true,
        Facing::WEST => true,
        Facing::EAST => true,
        Facing::UP => false,
        Facing::DOWN => false
    ];

    protected FastAABBWrapper $aaBBWrapper;
    protected FastAABBWrapper $groundCheckAABB;
    protected readonly bool $isNodesCentered;
    protected readonly string $cacheKey;

    public function __construct(protected int $maxFallBlocks, Entity $holder)
    {
        $this->cacheKey = $holder::class;

        $aaBB = $holder->getBoundingBox();
        $this->isNodesCentered = $aaBB->getXLength() <= 1 && $aaBB->getZLength() <= 1;

        $this->aaBBWrapper = FastAABBWrapper::fromEntityAABB($aaBB->contractedCopy(1 / 4, 0, 1 / 4));

        $groundAABB = FastAABBWrapper::centerAABB($aaBB);
        $groundAABB->maxY = 1;
        $this->groundCheckAABB = new FastAABBWrapper($groundAABB);
    }

    private function canFallSafelyFrom(Block $origin): ?Block
    {
        for ($i = 0; $i <= $this->maxFallBlocks; $i++) {
            $side = $origin->getSide(Facing::DOWN, $i);
            if (!$side->isSolid()) {
                continue;
            }

            return $side->getSide(Facing::UP);
        }

        return null;
    }

    private function canFit(World $world, Vector3 $origin): bool
    {
        $checkPos = $origin->add(0, 0.25, 0);
        if ($this->isNodesCentered) {
            $checkPos = $checkPos->add(0.5, 0, 0.5);
        }
        foreach ($this->aaBBWrapper->getCollisionBoxes($world, $checkPos) as $block => $aaBB) {
            if ($block instanceof Slab && $block->getSlabType()->equals(SlabType::BOTTOM())) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function canStillStandBelow(World $world, Vector3 $origin): bool
    {
        $checkPos = $origin->add(0, -1, 0);
        if ($this->isNodesCentered) {
            $checkPos = $checkPos->add(0.5, 0, 0.5);
        }
        foreach ($this->groundCheckAABB->getCollisionBoxes($world, $checkPos) as $block => $aaBB) {
            // ensure we can stand on top of the block just fine
            if ($block->getSide(Facing::UP)->isSolid()) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param World $world
     * @param Vector3 $current
     *
     * @return Generator<Vector3|array<int,float|Vector3>>
     */
    public function findNeighbors(World $world, Vector3 $current): Generator
    {
        $cachedNeighbors = ($cache = PathFindingNeighborCacheManager::getInstance()->get($world, $this->cacheKey))->retrieve($current);
        if ($cachedNeighbors !== null) {
            return yield from $cachedNeighbors;
        }

        $toCache = [];
        $currentBlock = $world->getBlock($current);
        if (!$this->canFit($world, $current)) {
            return yield from [];
        }
        foreach (Facing::ALL as $sideIndex) {
            $neighborVec3 = $current->getSide($sideIndex);
            $neighborBlock = $currentBlock->getSide($sideIndex);
            if (self::IS_HORIZONTAL[$sideIndex]) {
                if (
                    $neighborBlock->isSolid() || // the side is solid, we can't walk through that...
                    !$this->canFit($world, $neighborVec3)
                ) {
                    continue;
                }
                if ($this->canStillStandBelow($world, $current)) {
                    // we came from solid floor

                    // at this point, the neighbor block is not solid... so we check if there is a floor below us
                    if ($floorBlock = $this->canFallSafelyFrom($neighborBlock)) {
                        //$world->addParticle($neighborVec3->add(0.5, 0.5, 0.5), new FlameParticle());
                        yield $toCache[] = [$neighborVec3, $floorBlock->getPosition()->distanceSquared($current)];
                    }
                    continue;
                }
                // else we came from an air block

                // check if we have floor around us if there is no floor directly below us
                if ($this->canStillStandBelow($world, $neighborVec3)) {
                    yield $toCache[] = $neighborVec3;
                }
            } elseif ($sideIndex === Facing::UP) {
                if (!$this->canFit($world, $neighborVec3) || !$this->canStillStandBelow($world, $current)) {
                    continue;
                }

                foreach (Facing::HORIZONTAL as $side) {
                    $sideVec3 = $current->getSide($side);
                    if (
                        $this->canStillStandBelow($world, $topBlock = $sideVec3->getSide(Facing::UP)) &&
                        $this->canFit($world, $topBlock)
                    ) {
                        yield $toCache[] = $neighborVec3;
                    }
                }
            } elseif ($sideIndex === Facing::DOWN) {
                if (
                    $currentBlock->isSolid() ||
                    !($floorBlock = $this->canFallSafelyFrom($currentBlock)) ||
                    !$this->canFit($world, $floorBlock->getPosition())
                ) {
                    continue;
                }
                yield $toCache[] = [$floorBlock->getPosition(), $floorBlock->getPosition()->distanceSquared($current)];
            }
        }

        // protection just in case the generator doesn't actually terminate,
        // we don't want to end with a half-traversed cache
        $cache->indexNeighbors($current, ...$toCache);
    }
}