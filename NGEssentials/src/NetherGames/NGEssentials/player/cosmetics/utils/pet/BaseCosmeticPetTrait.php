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


namespace NetherGames\NGEssentials\player\cosmetics\utils\pet;

use InvalidArgumentException;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

trait BaseCosmeticPetTrait
{
    private const COSMETIC_COMPOUND_TAG = "CosmeticPetData";
    private const NETWORK_TYPE_ID_TAG = "NetworkTypeId";
    private const HEIGHT_TAG = "Height";
    private const WIDTH_TAG = "Width";
    private const EYE_HEIGHT_TAG = "EyeHeight";

    private ?EntitySizeInfo $entitySizeInfo = null;

    protected function initPetData(CompoundTag $nbt): void
    {
        $cosmeticPetData = $nbt->getCompoundTag(self::COSMETIC_COMPOUND_TAG);
        if ($cosmeticPetData === null) {
            throw new InvalidArgumentException("CosmeticPetData tag is missing");
        }

        $this->setNetworkTypeId($cosmeticPetData->getString(self::NETWORK_TYPE_ID_TAG));
        $this->setSize($this->entitySizeInfo = new EntitySizeInfo(
            $cosmeticPetData->getFloat(self::HEIGHT_TAG),
            $cosmeticPetData->getFloat(self::WIDTH_TAG),
            $cosmeticPetData->getFloat(self::EYE_HEIGHT_TAG)
        ));
    }

    public static function addCosmeticNBT(CompoundTag $nbt, string $networkTypeId, EntitySizeInfo $sizeInfo): CompoundTag
    {
        $cosmeticPetData = CompoundTag::create();
        $cosmeticPetData->setString(self::NETWORK_TYPE_ID_TAG, $networkTypeId);
        $cosmeticPetData->setFloat(self::HEIGHT_TAG, $sizeInfo->getHeight());
        $cosmeticPetData->setFloat(self::WIDTH_TAG, $sizeInfo->getWidth());
        $cosmeticPetData->setFloat(self::EYE_HEIGHT_TAG, $sizeInfo->getEyeHeight());

        $nbt->setTag(self::COSMETIC_COMPOUND_TAG, $cosmeticPetData);
        return $nbt;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return $this->entitySizeInfo ?? new EntitySizeInfo(1, 1);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::PLAYER;
    }
}