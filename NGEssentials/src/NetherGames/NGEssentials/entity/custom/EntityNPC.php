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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\entity\custom;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class EntityNPC extends Custom
{
    use NPCTrait;

    public function __construct(
        Location       $location,
        string         $name,
        private string $runtimeId,
        ?callable      $callable = null,
        ?callable      $nameCallable = null
    )
    {
        $this->setCallable($callable);
        $this->setNameCallable($nameCallable);

        parent::__construct($location, $name);

        $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
        $this->metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 2);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 1.5);
    }

    public function setScale(float $scale): void
    {
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 2 / $scale);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 1.5 / $scale);

        parent::setScale($scale);
    }

    public function updateMetadata(): void
    {
        parent::updateMetadata();
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
}