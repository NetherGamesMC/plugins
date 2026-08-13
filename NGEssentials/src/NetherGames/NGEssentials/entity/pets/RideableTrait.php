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


namespace NetherGames\NGEssentials\entity\pets;

use libVanilla\entity\ai\navigator\EntityNavigator;
use NetherGames\NGEssentials\entity\pets\state\RiddenState;
use NetherGames\NGEssentials\player\pets\events\PetUnmountEvent;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;

trait RideableTrait
{
    use DynamicEntityPetTrait;

    /** @var EntityLink[] */
    private array $links = [];

    public function sendSpawnPacket(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->getInternalNetworkTypeId(),
            ($location = $this->getLocation())->asVector3(),
            $this->getMotion(),
            $location->pitch,
            $location->yaw,
            $location->yaw,
            $location->yaw,
            array_map(static function (Attribute $attr): NetworkAttribute {
                return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
            }, $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            array_values($this->links)
        ));
    }

    public function addRider(Entity $rider): void
    {
        $riderNetworkProperties = $rider->getNetworkProperties();
        $riderNetworkProperties->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, $this->getRiderSeatPosition($rider));
        $riderNetworkProperties->setGenericFlag(EntityMetadataFlags::RIDING, true);
        $riderNetworkProperties->setGenericFlag(EntityMetadataFlags::WASD_CONTROLLED, true);

        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [SetActorLinkPacket::create(
            $this->links[$rider->getId()] = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER, true, true, 0.0)
        )]);

        $this->attemptAIOverride();

        $this->location->pitch = 0;
        $this->onMount($rider);

        $this->recalculateBoundingBox();
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, $this->getSize()->getHeight() + 1.8, 0);
    }

    private function attemptAIOverride(): void
    {
        if (!$this->hasRider()) {
            $this->setNavigator($this->getDefaultNavigator());
            $this->setState($this->getDefaultState());
        } else {
            $this->setNavigator(new EntityNavigator($this));
            $this->setState(new RiddenState($this));
        }
    }

    public function hasRider(): bool
    {
        return count($this->links) > 0;
    }

    protected function onMount(Entity $rider): void
    {
    }

    protected function recalculateBoundingBox(): void
    {
        parent::recalculateBoundingBox();

        if (!$this->hasRider()) {
            return;
        }
        foreach ($this->links as $riderId => $_) {
            $entity = $this->location->world->getEntity($riderId);
            if ($entity === null || $entity->isClosed()) {
                continue;
            }
            $this->boundingBox = $this->boundingBox->addCoord(
                0, $entity->getSize()->getHeight() + $this->getRiderSeatPosition($entity)->y, 0
            );
        }
    }

    public function isRiddenBy(Entity $rider): bool
    {
        return isset($this->links[$rider->getId()]);
    }

    public function onDispose(): void
    {
        foreach ($this->links as $riderId => $_) {
            $entity = $this->location->world->getEntity($riderId);
            if ($entity === null || $entity->isClosed()) {
                continue;
            }
            $this->removeRider($entity);
        }
        $this->links = [];

        parent::onDispose();
    }

    public function removeRider(Entity $rider): void
    {
        if (!isset($this->links[$index = $rider->getId()])) {
            return;
        }
        $riderNetworkProperties = $rider->getNetworkProperties();
        $riderNetworkProperties->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, Vector3::zero());
        $riderNetworkProperties->setGenericFlag(EntityMetadataFlags::RIDING, false);
        $riderNetworkProperties->setGenericFlag(EntityMetadataFlags::WASD_CONTROLLED, false);

        unset($this->links[$index]);

        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [SetActorLinkPacket::create(
            new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, true, true, 0.0)
        )]);

        $this->attemptAIOverride();

        if ($rider instanceof Player) {
            (new PetUnmountEvent($this, $rider))->call();
        }
        $this->onUnmount($rider);

        $this->recalculateBoundingBox();
    }

    protected function onUnmount(Entity $rider): void
    {
    }

    public function onRiderControl(Entity $rider, float $WS, float $AD): void
    {
        $directionVector = $rider->getDirectionVector();
        if ($WS != 0) {
            $directionVector = $directionVector->multiply($WS);
        }
        if ($AD != 0) {
            // http://answers.unity.com/answers/228224/view.html
            $directionVector = $directionVector->addVector(
                $directionVector
                    ->cross(new Vector3(0, 1, 0))
                    ->multiply($AD * -1)
            );
        }
        $this->getNavigator()->setGoal($this->getRiderSourcePosition($rider)?->addVector($directionVector->normalize()->multiply(3)));
    }

    protected function getRiderSourcePosition(Entity $rider): ?Vector3
    {
        return $rider->getEyePos();
    }
}