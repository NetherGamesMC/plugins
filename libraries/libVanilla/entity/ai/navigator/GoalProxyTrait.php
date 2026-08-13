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

use pocketmine\math\Vector3;

trait GoalProxyTrait
{
    private ?Vector3 $unsafeGoal = null;

    protected function getUnsafeGoal(): ?Vector3
    {
        return $this->unsafeGoal;
    }

    public function setGoal(?Vector3 $pos): void
    {
        $this->unsafeGoal = $pos;
    }

    protected function getTargetLocation(): ?Vector3
    {
        return $this->holder->getTargetEntity()?->getLocation() ?? $this->getUnsafeGoal();
    }

    abstract public function getGoal(): ?Vector3;
}