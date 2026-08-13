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


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\EntityBase;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class VillagerPet extends EntityBase implements IPetEntity
{
    use WalkingPetTrait {
        WalkingPetTrait::initPetData as private baseInitPetData;
    }

    public const PROFESSION_FARMER = 0;
    public const PROFESSION_LIBRARIAN = 1;
    public const PROFESSION_PRIEST = 2;
    public const PROFESSION_BLACKSMITH = 3;
    public const PROFESSION_BUTCHER = 4;

    public const TAG_PROFFESSION = 'Profession';

    public static function getNetworkTypeId(): string
    {
        return EntityIds::VILLAGER;
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->baseInitPetData($nbt);
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $nbt->getInt(self::TAG_PROFFESSION, mt_rand(0, 4)));
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6);
    }
}