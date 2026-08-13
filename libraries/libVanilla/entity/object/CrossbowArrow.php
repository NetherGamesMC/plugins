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

namespace libVanilla\entity\object;

use AssertionError;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\timings\Timings;
use ReflectionClass;

// https://minecraft.fandom.com/wiki/Crossbow
class CrossbowArrow extends Arrow
{
    public const TAG_PIERCE_COUNT = "pierceCount";

    /** @var array<int, bool> */
    private array $hitEntities = [];

    public function __construct(Location $location, ?Entity $shootingEntity, private int $pierceCount, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, true, $nbt);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->pierceCount = $nbt->getInt(self::TAG_PIERCE_COUNT, $this->pierceCount);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setInt(self::TAG_PIERCE_COUNT, $this->pierceCount);
        return $nbt;
    }

    public function getPierceCount(): int
    {
        return $this->pierceCount;
    }

    public function getResultDamage(): int
    {
        return 9;
    }

    private function unflagForDespawn(): void
    {
        $refClass = new ReflectionClass(Entity::class);
        $refProp = $refClass->getProperty("needsDespawn");
        $refProp->setValue($this, false);
    }

    protected function move(float $dx, float $dy, float $dz): void
    {
        $this->blocksAround = null;

        Timings::$entityMove->startTiming();

        $start = $this->location->asVector3();
        $end = $start->add($dx, $dy, $dz);

        $blockHit = null;
        $entityHit = null;
        $hitResult = null;

        try {
            foreach (VoxelRayTrace::betweenPoints($start, $end) as $vector3) {
                $block = $this->getWorld()->getBlockAt($vector3->x, $vector3->y, $vector3->z);

                $blockHitResult = $this->calculateInterceptWithBlock($block, $start, $end);
                if ($blockHitResult !== null) {
                    $end = $blockHitResult->hitVector;
                    $blockHit = $block;
                    $hitResult = $blockHitResult;
                    break;
                }
            }
        } catch (InvalidArgumentException $e) {
            $this->flagForDespawn();
        }

        $entityDistance = PHP_INT_MAX;

        $newDiff = $end->subtractVector($start);
        foreach ($this->getWorld()->getCollidingEntities($this->boundingBox->addCoord($newDiff->x, $newDiff->y, $newDiff->z)->expand(1, 1, 1), $this) as $entity) {
            if ($entity->getId() === $this->getOwningEntityId() && $this->ticksLived < 5) {
                continue;
            }
            if (isset($this->hitEntities[$entity->getId()])) {
                continue;
            }

            $entityBB = $entity->boundingBox->expandedCopy(0.3, 0.3, 0.3);
            $entityHitResult = $entityBB->calculateIntercept($start, $end);

            if ($entityHitResult === null) {
                continue;
            }

            $distance = $this->location->distanceSquared($entityHitResult->hitVector);

            if ($distance < $entityDistance) {
                $entityDistance = $distance;
                $entityHit = $entity;
                $hitResult = $entityHitResult;
                $end = $entityHitResult->hitVector;
            }
        }

        $this->location = Location::fromObject(
            $end,
            $this->location->world,
            $this->location->yaw,
            $this->location->pitch
        );
        $this->recalculateBoundingBox();

        if ($hitResult !== null) {
            if ($entityHit !== null) {
                $ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
                $this->hitEntities[$entityHit->getId()] = true;
            } elseif ($blockHit !== null) {
                $ev = new ProjectileHitBlockEvent($this, $hitResult, $blockHit);
            } else {
                throw new AssertionError("unknown hit type");
            }
            $wasCritical = $this->isCritical();

            $ev->call();
            $this->onHit($ev);

            if ($ev instanceof ProjectileHitEntityEvent) {
                $this->onHitEntity($ev->getEntityHit(), $ev->getRayTraceResult());

                if (count($this->hitEntities) <= $this->pierceCount) {
                    $this->setCritical($wasCritical);
                    $this->unflagForDespawn();
                }
            } elseif ($ev instanceof ProjectileHitBlockEvent) {
                $this->onHitBlock($ev->getBlockHit(), $ev->getRayTraceResult());

                $this->isCollided = $this->onGround = true;
                $this->motion = new Vector3(0, 0, 0);
            }
        } else {
            $this->isCollided = $this->onGround = false;
            $this->blockHit = null;

            //recompute angles...
            $f = sqrt(($this->motion->x ** 2) + ($this->motion->z ** 2));
            $this->setRotation(
                atan2($this->motion->x, $this->motion->z) * 180 / M_PI,
                atan2($this->motion->y, $f) * 180 / M_PI
            );
        }

        $this->getWorld()->onEntityMoved($this);
        $this->checkBlockIntersections();

        Timings::$entityMove->stopTiming();
    }
}