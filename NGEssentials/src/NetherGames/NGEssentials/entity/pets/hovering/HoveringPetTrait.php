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
 * @author k3ithos, matcracker, driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\hovering;

use libPhysX\PhysX;
use libVanilla\entity\ai\FlyEntityTrait;
use libVanilla\entity\ai\navigator\EntityNavigator;
use NetherGames\NGEssentials\entity\pets\PetEntityTrait;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

trait HoveringPetTrait
{
    use FlyEntityTrait, PetEntityTrait {
        PetEntityTrait::getDefaultState insteadof FlyEntityTrait;
        FlyEntityTrait::entityBaseTick as protected baseEntityBaseTick;
        PetEntityTrait::entityBaseTick insteadof FlyEntityTrait;
    }

    private float $flyHeight = 3;

    public function getDefaultNavigator(): EntityNavigator
    {
        return new HoveringEntityNavigator($this);
    }

    public function doMovement(Vector3 $location): void
    {
        $x = $location->x - $this->location->x;
        $y = $location->y - $this->location->y;
        $z = $location->z - $this->location->z;

        $dist = sqrt($x * $x + $y * $y + $z * $z);

        if ($dist < $this->getNavigator()->getAllowedMovementOffset()) {
            $this->stopMoving();
            return;
        }

        $speed = $this->getSpeed();
        $this->motion = new Vector3(
            $speed * 0.15 * ($x / $dist),
            $speed * 0.25 * ($y / $dist),
            $speed * 0.15 * ($z / $dist)
        );

        $rotation = PhysX::calculateRotationEulerAngle($this->location, $location);
        $this->location->yaw = $rotation->yaw;
        $this->location->pitch = $rotation->pitch;
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->gravity = 0;
        $this->setHasGravity(false);

        $ownerPos = $this->getOwningEntityInWorld()?->getLocation();
        assert($ownerPos instanceof Location);
        $this->teleport($this->getSafeLocation($ownerPos->addVector($this->getFollowOffset())));
    }

    public function getSafeLocation(Vector3 $reference): Vector3
    {
        $reference = $reference->add(0, $this->flyHeight, 0);
        return $this->location->world->getBlock($reference->add(0, $this->flyHeight, 0))->isSolid() ? $this->location->world->getSafeSpawn($reference) : $reference;
    }

    protected function tryChangeMovement(): void
    {
        if ($this->getNavigator()->getGoal() !== null) {
            $this->drag = 0.02;
            parent::tryChangeMovement();
            return;
        }
        $this->drag = 0.5;
        parent::tryChangeMovement();
        $this->drag = 0.02;
    }

    protected function onMount(Entity $rider): void
    {
        if ($rider instanceof Player && $rider->isSurvival() && !$rider->getAllowFlight()) {
            $rider->setAllowFlight(true);
        }
    }

    protected function onUnmount(Entity $rider): void
    {
        if ($rider instanceof Player && $rider->isSurvival() && $rider->getAllowFlight()) {
            if (!$rider->hasPermission(Permissions::RANK_ULTRA) || ($playerData = NGEssentials::getInstance()->getPlayerData())->getString($rider, PlayerData::SELECTED_RANK) === RankManager::NO_RANK || $playerData->getBool($rider, PlayerData::NICK)) {
                $rider->setAllowFlight(false);
            }
        }
    }

    protected function getRiderSourcePosition(Entity $rider): ?Vector3
    {
        return $this->getLocation();
    }
}