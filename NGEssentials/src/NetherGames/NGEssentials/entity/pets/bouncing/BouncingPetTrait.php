<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
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


namespace NetherGames\NGEssentials\entity\pets\bouncing;

use libPhysX\PhysX;
use libVanilla\entity\ai\AIEntityTrait;
use libVanilla\entity\ai\navigator\EntityNavigator;
use NetherGames\NGEssentials\entity\pets\PetEntityTrait;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

trait BouncingPetTrait
{
    use AIEntityTrait, PetEntityTrait {
        PetEntityTrait::getDefaultState insteadof AIEntityTrait;
        PetEntityTrait::tryLookAtOwner as private __lookAtOwner;
        PetEntityTrait::entityBaseTick insteadof AIEntityTrait;
        AIEntityTrait::entityBaseTick as protected baseEntityBaseTick;
    }

    private int $jumpCooldownUntil = 0;

    private ?Vector3 $lastPos = null;
    private ?float $jumpDistance = null;
    private bool $jumping = false;

    public function getDefaultNavigator(): EntityNavigator
    {
        return new BouncingPetNavigator($this);
    }

    public function getSafeLocation(Vector3 $reference): Vector3
    {
        return $this->location->world->getSafeSpawn($reference);
    }

    public function doMovement(Vector3 $location): void
    {
        /** @var Vector3 $motion */
        /** @var Rotation $rotation */
        [$motion, $rotation] = PhysX::calculateMRPhysic($this->getPosition(), $location, $this->getSpeed() / 4, movementOffset: $this->getNavigator()->getAllowedMovementOffset());

        if (($motion->x * $motion->x + $motion->z * $motion->z) < Entity::MOTION_THRESHOLD) {
            return;
        }

        if (!$this->jumping && (!$this->onGround || $this->hasJumpCooldown())) {
            return;
        }

        if ($this->jumpDistance !== null) {
            if ($this->jumpDistance <= 0) {
                $this->jumpDistance = null;
                $this->location->yaw = $rotation->yaw;
                return;
            }
            $scale = min($location->distance($this->location) / $this->jumpDistance, 1);
            $motion->x *= $scale;
            $motion->z *= $scale;
            $this->lastPos = null; // do not reset jump distance

            if (($motion->x * $motion->x + $motion->z * $motion->z) < Entity::MOTION_THRESHOLD) {
                return;
            }
        }

        $this->location->yaw = $rotation->yaw;
        $this->motion = new Vector3($motion->x, $this->motion->y, $motion->z);

        if (!$this->jumping) {
            $this->jump();
            $this->jumpCooldownUntil = $this->location->world->getServer()->getTick() + $this->getJumpCooldown();
        }
    }

    private function hasJumpCooldown(): bool
    {
        return $this->jumpCooldownUntil >= $this->location->world->getServer()->getTick();
    }

    public function jump(): void
    {
        if ($this->onGround) {
            $this->lastPos = $this->location->asVector3();
            $this->jumping = true;
        } else {
            $this->lastPos = null;
        }
        parent::jump();
    }

    public function getJumpCooldown(): int
    {
        return 30;
    }

    public function tryLookAtOwner(): void
    {
        if (!$this->isOnGround() || $this->hasJumpCooldown()) {
            return;
        }
        $this->__lookAtOwner();
    }

    protected function updateFallState(float $distanceThisTick, bool $onGround): ?float
    {
        if ($this->jumping && $onGround) {
            $this->jumping = false;
            // there should be a better way to calculate this with actual physics calculations
            if ($this->lastPos !== null) {
                $this->jumpDistance = $this->lastPos->distance($this->location->asVector3());
            }
        }
        return parent::updateFallState($distanceThisTick, $onGround);
    }
}