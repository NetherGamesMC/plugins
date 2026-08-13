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
 * @author Drew, Driesboy, CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\ai\state;

use libPhysX\PhysX;
use libVanilla\entity\Monster;

class AttackingState extends EntityState
{
    public function onTick(): bool
    {
        if (!$this->holder instanceof Monster) {
            return false;
        }

        $target = $this->holder->getTargetEntity();
        if ($target === null) {
            $myLocation = $this->holder->getLocation();
            if ($myLocation->pitch !== 0.0) {
                // reset pitch
                $this->holder->setRotation($myLocation->yaw, 0);

                return true;
            }

            return false;
        }

        $rotation = PhysX::calculateRotationEulerAngle($this->holder->getEyePos(), $target->getEyePos());
        $this->holder->setRotation($rotation->yaw, $rotation->pitch);

        if ($this->holder->isInRange($target->getLocation())) {
            if ($this->holder->getRemainingAttackCooldown() === (int)($this->holder::ATTACK_COOLDOWN / 2)) {
                $this->holder->prepareAttack();
            }
            $this->holder->onAttackTick();

            return true;
        } elseif (
            !$this->holder->isInteresting($target) ||
            $target->getWorld() !== $this->holder->getWorld() ||
            $target->getPosition()->distanceSquared($this->holder->getPosition()) > ($this->holder->getFollowDistance() ** 2)
        ) {
            $this->holder->resetAttack();
            $this->holder->setState(new WanderingState($this->holder));

            return true;
        } else {
            $this->holder->resetAttack();
        }

        return false;
    }
}