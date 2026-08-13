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
use libVanilla\entity\ai\navigator\SafeNavigator;
use libVanilla\entity\EntityBase;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

class WanderingState extends EntityState implements PassiveState, RandomizedState
{
    private int $wanderTicks;
    private Vector3 $wanderDirection;

    private function randomDirection(): Vector3
    {
        return (new Vector3(Utils::getRandomFloat() * 2 - 1, 0, Utils::getRandomFloat() * 2 - 1))->normalize();
    }

    public function __construct(EntityBase $holder)
    {
        $holder->setTargetEntity(null);
        $this->wanderTicks = mt_rand(5, 10) * 20;
        if (Utils::getRandomFloat() < 0.75) {
            $this->wanderDirection = $this->randomDirection();
        } else {
            // sometimes they bundle up so that they're not totally antisocial LOL
            $myLocation = $holder->getLocation();
            $nearestPartner = null;
            $searchDistance = 16;
            $nearestDist2 = $searchDistance * $searchDistance;
            $bb = $holder->getBoundingBox()->expandedCopy($searchDistance, $searchDistance, $searchDistance);

            foreach ($myLocation->world->getNearbyEntities($bb, $holder) as $nearbyEntity) {
                if (!$nearbyEntity instanceof $holder) {
                    continue;
                }
                $dist = $nearbyEntity->getLocation()->distanceSquared($myLocation);
                if ($dist > $nearestDist2) {
                    continue;
                }
                $nearestDist2 = $dist;
                $nearestPartner = $nearbyEntity;
            }
            $this->wanderDirection = $nearestPartner !== null ?
                $nearestPartner->getLocation()->subtractVector($myLocation)->normalize() :
                $this->randomDirection();
        }

        if (!$holder->getNavigator() instanceof SafeNavigator) {
            $holder->setNavigator(new SafeNavigator($holder));
        }

        parent::__construct($holder);
    }

    public function __destruct()
    {
        $this->holder->setNavigator($this->holder->getDefaultNavigator());
    }

    public function onTick(): bool
    {
        if (!$this->holder instanceof AIEntity) {
            return false;
        }

        if ($this->wanderTicks <= 0) {
            $sides = iterator_to_array($this->holder->getLocation()->getWorld()->getBlock($this->holder->getLocation())->getHorizontalSides());
            shuffle($sides);
            /** @var Block $side */
            foreach ($sides as $side) {
                if (!$side->isSolid()) {
                    $this->holder->lookAt($side->getPosition()->add(0.5, 0, 0.5));
                    break;
                }
            }
            $this->holder->setState(new RestingState($this->holder));
            $this->holder->scheduleDelayedUpdate(5);

            return false;
        }
        $this->wanderTicks--;

        $nextPosition = $this->holder->getLocation();

        $nextPosition->x += $this->wanderDirection->x;
        $nextPosition->z += $this->wanderDirection->z;

        $collidingBlock = $this->holder->getWorld()->getBlock($nextPosition);
        if ($collidingBlock->isSolid()) {
            if ($collidingBlock->getSide(Facing::UP)->isSolid()) {
                $stuck = true;

                $sides = iterator_to_array($collidingBlock->getHorizontalSides());
                shuffle($sides);
                /** @var Block $side */
                foreach ($sides as $side) {
                    if (!$side->isSolid()) {
                        $this->wanderDirection = $side->getPosition()->add(0.5, 0, 0.5)->subtractVector($this->holder->getLocation())->normalize();
                        $stuck = false;
                        break;
                    }
                }

                if ($stuck) {
                    $this->holder->setState(new RestingState($this->holder));
                    $this->holder->scheduleDelayedUpdate(5);

                    return false;
                }
            } else {
                $this->holder->jump();
            }
        }

        $this->wanderDirection->x += (Utils::getRandomFloat() * 2 - 1) * (1 / 32);
        $this->wanderDirection->z += (Utils::getRandomFloat() * 2 - 1) * (1 / 32);
        $this->wanderDirection = $this->wanderDirection->normalize();

        $this->holder->setRotation($this->holder->getLocation()->getYaw(), 0);

        $this->holder->getNavigator()->setGoal($nextPosition);

        return true;
    }
}