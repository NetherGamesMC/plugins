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


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\ai\navigator\dumper\LightMapPathDumper;
use libVanilla\entity\ai\navigator\pathfinding\AStarPathFindingAlgorithm;
use libVanilla\entity\ai\navigator\pathfinding\DistanceFunction;
use libVanilla\entity\ai\navigator\pathfinding\LazyPathFindingNavigator;
use libVanilla\entity\ai\navigator\pathfinding\neighbor\DefaultNeighborResolver;
use libVanilla\entity\ai\navigator\PathFindingNavigator;
use libVanilla\entity\ai\navigator\SafeNavigator;
use libVanilla\entity\EntityBase;
use NetherGames\NGEssentials\NGEssentials;

class WalkingPetNavigator extends LazyPathFindingNavigator
{
    public function __construct(EntityBase $holder)
    {
        parent::__construct(
            $holder,
            new SafeNavigator($holder, 2),
            new PathFindingNavigator(
                $holder,
                new AStarPathFindingAlgorithm(
                    DistanceFunction::euclideanSquared(...),
                    DistanceFunction::manhattan(...),
                    new DefaultNeighborResolver(3 + ($holder->getMaxHealth() / 2), $holder),
                    16
                ), NGEssentials::isInDevelopmentMode() ? new LightMapPathDumper() : null
            )
        );
    }

    public function getAllowedMovementOffset(): float
    {
        if ($this->shouldUsePathfinding()) {
            return 0;
        }
        return $this->holder->getSize()->getWidth() * 0.25;
    }

    protected function shouldUsePathfinding(): bool
    {
        return $this->holder->isCollidedHorizontally;
    }
}