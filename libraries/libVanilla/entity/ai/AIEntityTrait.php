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

namespace libVanilla\entity\ai;


use InvalidArgumentException;
use libVanilla\entity\ai\state\EntityState;
use libVanilla\entity\ai\state\PassiveState;
use libVanilla\entity\ai\state\RestingState;
use libVanilla\entity\traits\BoundingBoxPushTrait;
use libVanilla\utils\TimingsStore;
use pocketmine\scheduler\ClosureTask;
use function array_key_first;

trait AIEntityTrait
{
    use BoundingBoxPushTrait;

    protected ?EntityState $state = null;

    public function setState(?EntityState $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?EntityState
    {
        return $this->state;
    }

    public function getDefaultState(): EntityState
    {
        return new RestingState($this);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->state === null) {
            $this->setState($this->getDefaultState());
        }

        if (
            ($this->ticksLived % 5) === 0 &&
            $this->getTargetEntity() === null &&
            $this->state instanceof PassiveState
        ) {
            $this->setTargetEntity($this->getNearestInterestingTarget());
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $stateUpdate = false;
        if ($this->state !== null) {
            $timings = TimingsStore::getInstance()->getWithParent("entityStateTick", TimingsStore::shortName($this->state));

            $timings->startTiming();
            $stateUpdate = $this->state->onTick();
            $timings->stopTiming();
        }

        $navigator = $this->getNavigator();
        $timings = TimingsStore::getInstance()->getWithParent("entityNavigator", TimingsStore::shortName($navigator));

        $timings->startTiming();
        $goal = $navigator->getGoal();
        $timings->stopTiming();

        if ($goal !== null) {
            $this->doMovement($goal);
        }

        return $hasUpdate || $stateUpdate;
    }

    public function scheduleDelayedUpdate(int $delay): void
    {
        if ($delay < 1) {
            throw new InvalidArgumentException("Update delay must not go below 1");
        }

        $plugins = $this->getWorld()->getServer()->getPluginManager()->getPlugins();
        $plugins[array_key_first($plugins)]->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            if ($this->isClosed() || $this->isFlaggedForDespawn()) {
                return;
            }
            $this->scheduleUpdate();
        }), $delay - 1);
    }
}