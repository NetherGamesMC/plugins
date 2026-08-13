<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils;

use NetherGames\NGEssentials\entity\custom\Custom;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class CageEntity extends Custom
{
    public function __construct(
        Location                 $location,
        private readonly string  $runtimeId,
        private readonly ?string $spawnAnimation
    )
    {
        parent::__construct($location, '');

        $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 0);
        $this->metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
    }

    public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket
    {
        $location = $this->getLocation();

        return AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->runtimeId,
            $location->asVector3(),
            null,
            $location->getPitch(),
            $location->getYaw(),
            $location->getYaw(),
            $location->getYaw(),
            [],
            $this->metadata->getAll(),
            new PropertySyncData([], []),
            []
        );
    }

    public function getSpawnAnimation(): ?ClientboundPacket
    {
        return $this->spawnAnimation === null ? null : AnimateEntityPacket::create($this->spawnAnimation, '', '', 0, '', 0, [$this->getId()]);
    }
}