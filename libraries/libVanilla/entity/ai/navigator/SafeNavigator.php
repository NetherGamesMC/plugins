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

namespace libVanilla\entity\ai\navigator;

use libVanilla\entity\EntityBase;
use pocketmine\block\Liquid;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class SafeNavigator extends EntityNavigator
{
    use GoalProxyTrait;

    public function __construct(EntityBase $holder, private ?int $tolerableFallDistance = null)
    {
        parent::__construct($holder);
    }

    /**
     * This will return a SAFER goal back, we are wrapping the unsafe goal internally.
     */
    public function getGoal(): ?Vector3
    {
        if (($targetLocation = $this->getTargetLocation()) === null) {
            return null;
        }

        $myLocation = $this->holder->getLocation();

        if ($this->holder->getTargetEntity() !== null && $this->holder->isInRange($targetLocation)) {
            return null;
        }

        $directionVector = $myLocation->addVector($targetLocation->subtractVector($myLocation)->normalize());
        $floorToWalk = $myLocation->world->getBlock($directionVector->subtract(0, 1, 0));

        if (
            $this->holder->isCollidedHorizontally &&
            $myLocation->world->getBlock($myLocation->subtract(0, 1, 0))->isSolid() &&
            ($obstruction = $floorToWalk->getSide(Facing::UP))->isSolid() &&
            !($spaceAbove = $obstruction->getSide(Facing::UP))->isSolid() // not a 2-high wall
        ) {
            return $spaceAbove->getPosition()->add(0.5, 0.5, 0.5);
        }

        $fallDistance = $this->tolerableFallDistance ?? (3 + $this->holder->getHealth() / 2);
        for ($i = 0; $i < $fallDistance; $i++) {
            if ($floorToWalk->isSolid()) {
                return $targetLocation;
            }
            if ($floorToWalk instanceof Liquid /* todo: check if water animal or can breathe in water */) {
                return null;
            }
            $floorToWalk = $floorToWalk->getSide(Facing::DOWN);
        }

        return null;
    }
}