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

namespace libVanilla\entity\ai\navigator;

use libVanilla\entity\EntityBase;
use pocketmine\math\Vector3;

class EntityNavigator
{
    protected ?Vector3 $goal = null;

    public function __construct(protected EntityBase $holder)
    {
    }

    /**
     * No goal = no movement
     */
    public function getGoal(): ?Vector3
    {
        if (($target = $this->holder->getTargetEntity()) !== null) {
            return $target->getLocation();
        }
        return $this->goal;
    }

    public function setGoal(?Vector3 $pos): void
    {
        $this->goal = $pos;
    }

    /**
     * Returns the allowed movement offset, the acceptable distance (SQUARED) between the entity and the goal
     */
    public function getAllowedMovementOffset(): float
    {
        return min($this->holder->getBoundingBox()->getXLength() * 0.7, 0.7);
    }
}