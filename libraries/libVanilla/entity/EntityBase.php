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
 * @author Drew, Driesboy
 *
 */
declare(strict_types=1);

namespace libVanilla\entity;

use libVanilla\entity\ai\navigator\EntityNavigator;
use libVanilla\entity\ai\navigator\SafeNavigator;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

abstract class EntityBase extends Living
{

    /** @var EntityNavigator */
    protected EntityNavigator $navigator;
    /** @var float */
    private float $speed = 1.0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->jumpVelocity = 0.5;
    }

    /**
     * @return static
     */
    public function createEntity(Location $location, ?CompoundTag $nbt = null): self
    {
        /* @phpstan-ignore-next-line */
        return new static($location, $nbt);
    }

    public function getNearestInterestingTarget(): ?Entity
    {
        $pos = $this->getPosition();
        $lowestDistance = PHP_INT_MAX;
        $nearest = null;

        foreach ($this->getViewers() as $viewer) {
            $targetPosition = $viewer->getPosition();
            $distance = $pos->distanceSquared($targetPosition);
            if (
                $distance < $lowestDistance &&
                $this->isInFollowRange($targetPosition) &&
                $this->isInteresting($viewer)
            ) {
                $lowestDistance = $distance;
                $nearest = $viewer;
            }
        }

        return $nearest;
    }

    public function isInteresting(Entity $entity): bool
    {
        return false;
    }

    public function getSpeed(): float
    {
        return $this->speed;
    }

    public function setSpeed(float $speed): void
    {
        $this->speed = $speed;
    }

    public function getNavigator(): EntityNavigator
    {
        return $this->navigator ??= $this->getDefaultNavigator();
    }

    public function setNavigator(EntityNavigator $navigator): void
    {
        $this->navigator = $navigator;
    }

    public function getDefaultNavigator(): EntityNavigator
    {
        return new SafeNavigator($this);
    }

    public function getInteractDistance(): float
    {
        return 1;
    }

    public function getFollowDistance(): float
    {
        return 7; // sqrt(50) = 7.071
    }

    final public function isInRange(Vector3 $target): bool
    {
        return $target->distance($this->location) < $this->getInteractDistance();
    }

    final public function isInFollowRange(Vector3 $target): bool
    {
        return $target->distance($this->location) < $this->getFollowDistance();
    }

    /**
     * This is necessary after mutations such as scale, size, AABB changes,
     * etc. anything affected that is necessary for smart navigation.
     */
    private function resetNavigator(): void
    {
        $this->navigator = $this->getDefaultNavigator();
    }

    public function setScale(float $value): void
    {
        parent::setScale($value);
        $this->resetNavigator(); // we need to refresh with a new AABB
    }
}
