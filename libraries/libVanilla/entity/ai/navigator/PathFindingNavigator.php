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

use libVanilla\entity\ai\navigator\dumper\PathDumper;
use libVanilla\entity\ai\navigator\pathfinding\path\NavigationPath;
use libVanilla\entity\ai\navigator\pathfinding\path\PathNode;
use libVanilla\entity\ai\navigator\pathfinding\PathFindingAlgorithm;
use libVanilla\entity\EntityBase;
use libVanilla\utils\TimingsStore;
use pocketmine\math\Vector3;

class PathFindingNavigator extends EntityNavigator
{
    use GoalProxyTrait;

    private ?Vector3 $lastPosition = null;
    private ?Vector3 $lastUnsafeGoal = null;
    private ?NavigationPath $lastPath = null;
    private bool $ignoreNodeY = false;
    private readonly bool $isNodesCentered;

    public function __construct(
        EntityBase                   $holder,
        private PathFindingAlgorithm $algorithm,
        private ?PathDumper          $pathDumper = null
    )
    {
        $aaBB = $holder->getBoundingBox();
        $this->isNodesCentered = $aaBB->getXLength() <= 1 && $aaBB->getZLength() <= 1;
        parent::__construct($holder);
    }

    /*private function isCurrentPathfindingTick(): bool
    {
        return $this->holder->ticksLived % (($this->holder->getId() * 5) % 200 + 1) === 0;
    }*/

    private function isAtNode(PathNode|Vector3 $node): bool
    {
        $nodePos = ($node instanceof PathNode ? $node->getPosition() : $node);
        if ($this->isNodesCentered) {
            $holderPosFloored = $this->holder->getPosition()->floor();
            return
                $holderPosFloored->x == $nodePos->x &&
                $holderPosFloored->z == $nodePos->z &&
                ($this->ignoreNodeY || $holderPosFloored->y == $nodePos->y);
        }
        return $this->holder->getBoundingBox()->isVectorInside(
            $nodePos->add(0, $this->holder->getSize()->getHeight() / 2, 0)
        );
    }

    public function getGoal(): ?Vector3
    {
        if (($targetLocation = $this->getTargetLocation()) === null) {
            return null;
        }

        $myLocation = $this->holder->getLocation();

        if ($this->holder->getTargetEntity() !== null && $this->holder->isInRange($targetLocation)) {
            return null; // don't move now it only makes it awkward
        }

        $holderPosFloored = $myLocation->floor();
        $unsafePosFloored = $targetLocation->floor();

        if (
            !isset($this->lastPath) ||
            !isset($this->lastPosition) ||
            !isset($this->lastUnsafeGoal) ||
            (
            (!$this->lastUnsafeGoal->equals($unsafePosFloored) || $this->lastPath->isFinished())/* &&
                $this->isCurrentPathfindingTick()*/
                // tick-distribution causes weird situation where the entity just stares, enable for performance!
            )
        ) {
            $timings = TimingsStore::getInstance()->getWithParent("PathFinding", $this->holder->getName());

            $timings->startTiming();
            $this->lastPath = $this->algorithm->search($myLocation->world, $myLocation->floor(), $targetLocation->floor());

            if ($this->lastPath === null && ($width = $this->holder->getBoundingBox()->getXLength()) > 1) {
                $halfWidthBlocks = (int)ceil($width / 2);
                for ($x = -$halfWidthBlocks; $x <= $halfWidthBlocks; $x++) {
                    for ($z = -$halfWidthBlocks; $z <= $halfWidthBlocks; $z++) {
                        $this->lastPath = $this->algorithm->search($myLocation->world, $myLocation->add($x, 0, $z)->floor(), $targetLocation->floor());
                        if ($this->lastPath !== null) {
                            break 2;
                        }
                    }
                }
            }

            if ($this->lastPath !== null) {
                // try to truncate the path's start, cuz we're sometimes in the second node
                // (THIS IS A HACK THAT DEPENDS ON NORMAL WALK SPEED)
                $nodes = $this->lastPath->toPositionArray();
                if (count($nodes) > 1 && $this->isAtNode($nodes[1])) {
                    for ($i = 0; $i < 2; $i++) {
                        $this->lastPath->next();
                    }
                }
            }

            $timings->stopTiming();

            $this->lastPosition = $holderPosFloored;
            $this->lastUnsafeGoal = $unsafePosFloored;
        }

        if ($this->lastPath === null || $this->lastPath->isFinished()) {
            return null; // no path, or path is finished... do not move
        }

        $this->pathDumper?->showPath($myLocation->world, $this->lastPath->toPositionArray());

        if ($this->isAtNode($node = $this->lastPath->peek())) {
            $this->lastPath->next();
        }
        return $this->isNodesCentered ? $node->getPosition()->add(0.5, 0, 0.5) : $node->getPosition();
    }

    public function getAllowedMovementOffset(): float
    {
        if ($this->holder->getTargetEntity() !== null) {
            return ($this->holder->size->getWidth() / 2) ** 2;
        }
        return parent::getAllowedMovementOffset();
    }

    public function setIgnoreNodeY(bool $ignoreNodeY = true): self
    {
        $this->ignoreNodeY = $ignoreNodeY;
        return $this;
    }
}