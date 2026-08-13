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

class CustomFakeMovingBlock extends CustomFakeBlock
{
    public function __construct(Location $location, Block $block)
    {
        parent::__construct($location, '', $block);

        $this->metadata->setByte(EntityMetadataProperties::MINECART_HAS_DISPLAY, 1);
        $this->metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
        $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
    }

    public function getMetadataPacket(TypeConverter $typeConverter): SetActorDataPacket
    {
        $this->metadata->setInt(EntityMetadataProperties::MINECART_DISPLAY_BLOCK, $typeConverter->getBlockTranslator()->internalIdToNetworkId($this->getBlock()->getStateId()));

        return parent::getMetadataPacket($typeConverter);
    }

    public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket
    {
        $this->metadata->setInt(EntityMetadataProperties::MINECART_DISPLAY_BLOCK, $typeConverter->getBlockTranslator()->internalIdToNetworkId($this->getBlock()->getStateId()));
        $location = $this->getLocation();

        return AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            EntityIds::MINECART,
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