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

namespace libVanilla\entity\traits;

use Generator;
use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\ai\state\RandomizedState;
use libVanilla\entity\ai\state\RestingState;
use libVanilla\utils\TimingsStore;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\format\Chunk;

trait BoundingBoxPushTrait
{
    /**
     * @param AxisAlignedBB $bb
     * @return Generator<Entity>
     */
    private function fastFindCollidingEntities(AxisAlignedBB $bb): Generator
    {
        $minX = ((int)floor($bb->minX - 1)) >> Chunk::COORD_BIT_SIZE;
        $maxX = ((int)floor($bb->maxX + 1)) >> Chunk::COORD_BIT_SIZE;
        $minZ = ((int)floor($bb->minZ - 1)) >> Chunk::COORD_BIT_SIZE;
        $maxZ = ((int)floor($bb->maxZ + 1)) >> Chunk::COORD_BIT_SIZE;

        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                if (!$this->location->world->isChunkLoaded($x, $z)) {
                    continue;
                }
                foreach ($this->location->world->getChunkEntities($x, $z) as $entity) {
                    if ($entity === $this || !$entity->boundingBox->intersectsWith($bb)) {
                        continue;
                    }
                    yield $entity;
                }
            }
        }
    }

    protected function tryChangeMovement(): void
    {
        parent::tryChangeMovement();

        $myBB = $this->getBoundingBox();
        $force = clone $this->motion;
        if ($force->lengthSquared() < Entity::MOTION_THRESHOLD) {
            return;
        }

        $collided = false;
        $timings = TimingsStore::getInstance()->getWithParent("boundingBoxPushTrait", "Offset Calculation");
        foreach ($this->fastFindCollidingEntities($myBB->addCoord($force->x, $force->y, $force->z)) as $collidingEntity) {
            $timings->startTiming();
            $force->x = $collidingEntity->boundingBox->calculateXOffset($myBB, $force->x) * 1.02;
            $force->z = $collidingEntity->boundingBox->calculateZOffset($myBB, $force->z) * 1.02;
            $timings->stopTiming();
            $collided = true;
        }
        $this->motion = $force;

        if ($collided && $this instanceof AIEntity && $this->getState() instanceof RandomizedState) {
            // we stop moving, we rest...
            $this->setState(new RestingState($this));
        }
    }
}