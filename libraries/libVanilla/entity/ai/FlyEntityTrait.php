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

namespace libVanilla\entity\ai;

use libPhysX\internal\Rotation;
use libPhysX\PhysX;
use libVanilla\entity\EntityBase;
use pocketmine\math\Vector3;

/**
 * This trait override most methods in the {@link EntityBase} abstract class.
 */
trait FlyEntityTrait
{
    use AIEntityTrait;

    public function doMovement(Vector3 $location): void
    {
        /** @var Vector3 $motion */
        /** @var Rotation $rotation */
        [$motion, $rotation] = PhysX::calculateMRPhysic($this->getPosition()->asVector3(), $location->add(0, $this->getFlyHeight(), 0), $this->getSpeed() / 4, false);

        if ($this->motion->lengthSquared() <= $motion->lengthSquared()) {
            $this->motion = $motion;
        }
        $this->location->yaw = $rotation->yaw;
    }

    public function getFlyHeight(): int
    {
        return 5;
    }

    protected function updateFallState(float $distanceThisTick, bool $onGround): ?float
    {
        return null; // no fall damage
    }
}
