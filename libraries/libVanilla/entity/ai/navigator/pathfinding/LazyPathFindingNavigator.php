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

namespace libVanilla\entity\ai\navigator\pathfinding;

use libVanilla\entity\ai\navigator\EntityNavigator;
use libVanilla\entity\ai\navigator\PathFindingNavigator;
use libVanilla\entity\EntityBase;
use pocketmine\math\Vector3;

abstract class LazyPathFindingNavigator extends EntityNavigator
{
    protected const PATHFINDING_DECAY_TIME = 20;

    private int $forcePathFinding = 0;

    public function __construct(EntityBase $holder, protected EntityNavigator $primary, protected PathFindingNavigator $pathFinder)
    {
        parent::__construct($holder);
    }

    public function setGoal(?Vector3 $pos): void
    {
        $this->primary->setGoal($pos);
        $this->pathFinder->setGoal($pos);
    }

    abstract protected function shouldUsePathfinding(): bool;

    public function getGoal(): ?Vector3
    {
        if ($this->shouldUsePathfinding()) {
            $this->forcePathFinding = static::PATHFINDING_DECAY_TIME;
            return $this->pathFinder->getGoal();
        }
        if (($this->forcePathFinding -= 1) > 0) {
            return $this->pathFinder->getGoal();
        }
        return $this->primary->getGoal() ?? $this->pathFinder->getGoal();
    }
}