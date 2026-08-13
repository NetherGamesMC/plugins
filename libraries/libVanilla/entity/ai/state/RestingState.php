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

namespace libVanilla\entity\ai\state;

use libPhysX\PhysX;
use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\EntityBase;
use pocketmine\entity\Entity;

class RestingState extends EntityState implements PassiveState
{
    private const LOOK_DISTANCE_SQUARED = 7 ** 2;

    private int $restingTicks;
    private ?Entity $lookTarget = null;

    public function __construct(EntityBase $holder)
    {
        $holder->getNavigator()->setGoal(null);
        $this->restingTicks = mt_rand(15, 30) * 20;
        parent::__construct($holder);
    }

    private function findLookTarget(): void
    {
        $myLocation = $this->holder->getLocation();
        $this->lookTarget = null;
        $nearestDist2 = self::LOOK_DISTANCE_SQUARED;
        foreach ($this->holder->getViewers() as $viewer) {
            if ($viewer->isSpectator()) {
                continue;
            }
            $distance2 = $viewer->getEyePos()->distanceSquared($myLocation);
            if ($distance2 < $nearestDist2) {
                $nearestDist2 = $distance2;
                $this->lookTarget = $viewer;
            }
        }
    }

    public function onTick(): bool
    {
        if (!$this->holder instanceof AIEntity) {
            return false;
        }
        if ($this->restingTicks <= 0) {
            $this->holder->setState(new WanderingState($this->holder));
            return true;
        }
        $this->restingTicks--;

        $myLocation = $this->holder->getLocation();
        if ($this->lookTarget !== null && !$this->lookTarget->isClosed() && $this->lookTarget->isAlive()) {
            $rotation = PhysX::calculateRotationEulerAngle($this->holder->getEyePos(), $this->lookTarget->getEyePos());

            // convert -180-180 to 0-360
            $targetYawNormalized = $rotation->yaw + 180;
            $sourceYawNormalized = $myLocation->yaw + 180;
            $yawDiff = min(45, $targetYawNormalized - $sourceYawNormalized);
            $sourceYawNormalized = $sourceYawNormalized + $yawDiff - 180;
            if ($sourceYawNormalized < 0) {
                $sourceYawNormalized += 360.0;
            }

            // convert -90-90 to 0-180
            $targetPitchNormalized = $rotation->pitch + 90;
            $sourcePitchNormalized = $myLocation->pitch + 90;
            $pitchDiff = min(15, $targetPitchNormalized - $sourcePitchNormalized);
            $sourcePitchNormalized = $sourcePitchNormalized + $pitchDiff - 90;

            $this->holder->setRotation($sourceYawNormalized, $sourcePitchNormalized);
        } elseif ($myLocation->pitch !== 0.0) {
            // reset pitch only
            $this->holder->setRotation($myLocation->yaw, 0);
        }

        $this->findLookTarget();
        return true;
    }
}