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

use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\EntityBase;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class EvadingState extends EntityState implements PassiveState // apparently animals go back after showing food
{
    private int $evadeTimer = 60 * 20; // we get tired after 1 minute of trying to evade
    private Vector3 $safePosition;

    public function __construct(EntityBase $holder, protected Entity $attacker)
    {
        parent::__construct($holder);
        $this->updateSafePosition();
    }

    protected function getSafePosition(): Vector3
    {
        $holderLocation = $this->holder->getLocation();

        $safePosition = $holderLocation->addVector(
            $holderLocation
                ->subtractVector($this->attacker->getLocation())
                ->normalize()
                ->multiply($this->holder->getFollowDistance() * 2)
        );
        $safePosition->y = $holderLocation->world->getHighestBlockAt($safePosition->getFloorX(), $safePosition->getFloorZ()) + 1;

        return $safePosition;
    }

    public function updateSafePosition(): void
    {
        $this->holder->getNavigator()->setGoal($this->safePosition = $this->getSafePosition());
    }

    public function onTick(): bool
    {
        if (!$this->holder instanceof AIEntity) {
            return false;
        }

        if (--$this->evadeTimer <= 0 || $this->holder->isInRange($this->safePosition) || ($this->attacker->isClosed() || !$this->attacker->isAlive())) {
            $this->holder->setState(new RestingState($this->holder));

            return false;
        }

        if ($this->holder->isInFollowRange($this->attacker->getLocation())) {
            $this->updateSafePosition();
        }

        return true;
    }
}