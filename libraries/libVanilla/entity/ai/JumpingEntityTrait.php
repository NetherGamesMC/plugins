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

namespace libVanilla\entity\ai;

use libPhysX\internal\Rotation;
use libPhysX\PhysX;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

trait JumpingEntityTrait
{
    use AIEntityTrait;

    private int $jumpCooldownUntil = 0;

    public function doMovement(Vector3 $location): void
    {
        /** @var Vector3 $motion */
        /** @var Rotation $rotation */
        [$motion, $rotation] = PhysX::calculateMRPhysic($this->getPosition(), $location, $this->getSpeed() / 4);
        $this->location->yaw = $rotation->yaw;

        if (
            !$this->onGround ||
            $motion->lengthSquared() < Entity::MOTION_THRESHOLD // we are already on the destination, no horizontal motion
        ) {
            return;
        }

        if ($this->jumpCooldownUntil < ($currentTick = $this->getWorld()->getServer()->getTick())) {
            $this->motion = new Vector3($motion->x, $this->motion->y, $motion->z);
            $this->jump();
            $this->jumpCooldownUntil = $currentTick + $this->getJumpCooldown();
        }
    }

    public function getJumpCooldown(): int
    {
        return 30;
    }
}