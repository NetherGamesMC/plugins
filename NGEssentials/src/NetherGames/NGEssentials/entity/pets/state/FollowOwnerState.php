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


namespace NetherGames\NGEssentials\entity\pets\state;

use libVanilla\entity\ai\state\EntityState;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use UnexpectedValueException;

final class FollowOwnerState extends EntityState
{
    private const TELEPORTATION_DISTANCE_SQUARED = 16 * 16;
    private const FOLLOW_DISTANCE_SQUARED = 7 * 7;

    private int $positionSeekTick = 60;

    private ?int $preWanderTicks = null;

    public function onTick(): bool
    {
        $holder = $this->holder;

        if (!($holder instanceof IPetEntity)) {
            throw new UnexpectedValueException("Holder must implement " . IPetEntity::class);
        }

        if ($this->positionSeekTick > 0) {
            $this->positionSeekTick--;
        }

        $oPos = $holder->getOwningEntityInWorld()?->getLocation();
        if ($oPos === null) {
            return false;
        }

        $dist2 = $oPos->distanceSquared($holder->getLocation());
        $offset = $holder->getFollowOffset();
        if ($this->positionSeekTick === 0) {
            $this->positionSeekTick = 60;
            if ($dist2 > self::TELEPORTATION_DISTANCE_SQUARED) {
                $holder->stopMoving();
                $holder->lerpTeleport($oPos->addVector($holder->refreshFollowOffset()));
                $this->startWanderCountdown(true);
            } elseif ($dist2 > $offset->lengthSquared()) {
                $holder->moveTo($oPos->addVector($offset));
                $this->startWanderCountdown();
            }
            $holder->tryLookAtOwner();
        } elseif ($dist2 > self::FOLLOW_DISTANCE_SQUARED) {
            $holder->moveTo($oPos->addVector($offset));
            $holder->tryLookAtOwner();
            $this->startWanderCountdown(true);
        }

        if ($this->preWanderTicks !== null && --$this->preWanderTicks === 0) {
            $holder->refreshFollowOffset();
            $this->startWanderCountdown(true);
        }

        return true;
    }

    private function startWanderCountdown(bool $restart = false): void
    {
        if ($this->preWanderTicks !== null && !$restart) {
            return;
        }
        $this->preWanderTicks = mt_rand(3, 5) * 20;
    }
}