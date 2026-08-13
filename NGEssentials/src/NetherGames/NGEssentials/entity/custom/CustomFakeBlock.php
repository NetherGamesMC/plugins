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

use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class CustomFakeBlock extends Custom
{

    public function __construct(Location $location, string $username, private Block $block, float $scale = 0.85)
    {
        parent::__construct($location, $username);

        $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
        $this->metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);

        $this->setScale($scale);
    }

    public function setBlock(Block $block): void
    {
        $this->block = $block;
    }

    public function getMetadataPacket(TypeConverter $typeConverter): SetActorDataPacket
    {
        if (!$this instanceof CustomFakeMovingBlock) {
            $this->metadata->setInt(EntityMetadataProperties::VARIANT, $typeConverter->getBlockTranslator()->internalIdToNetworkId($this->getBlock()->getStateId()));
        }

        return parent::getMetadataPacket($typeConverter);
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket
    {
        $this->metadata->setInt(EntityMetadataProperties::VARIANT, $typeConverter->getBlockTranslator()->internalIdToNetworkId($this->getBlock()->getStateId()));
        $location = $this->getLocation();

        return AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            EntityIds::FALLING_BLOCK,
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