<?php

declare(strict_types=1);

namespace skywars\entities;

use libVanilla\entity\passive\Sheep;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class JebSheep extends Sheep
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setCanSaveWithChunk(false);
    }

    /**
     * @param Entity $riding
     */
    public function ride(Entity $riding): void
    {
        $this->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, $this->getEyeHeight() + 1, 0));

        $packet = new SetActorLinkPacket();

        $packet->link = new EntityLink($riding->getId(), $this->getId(), EntityLink::TYPE_PASSENGER, true, false, 0.0);
        NetworkBroadcastUtils::broadcastPackets($this->getWorld()->getPlayers(), [$packet]);
    }
}