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


namespace NetherGames\NGEssentials\entity\pets\hovering;

use libPhysX\internal\Rotation;
use libVanilla\entity\Monster;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class WitherPet extends Monster implements IPetEntity
{
    use HoveringNoCollisionTrait {
        initPetData as baseInitPetData;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::WITHER;
    }

    protected static function getOffsetDistanceFromPlayer(): float
    {
        return 3;
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->baseInitPetData($nbt);
        $this->getNetworkProperties()->setShort(EntityMetadataProperties::WITHER_AERIAL_ATTACK, -0x8000);
    }

    public function tryLookAtOwner(): void
    {
        // noop, broken for some reason
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(3, 1, 2.7);
    }

    protected function getClientSideRotation(): Rotation
    {
        return new Rotation($this->location->yaw, max(-45, $this->location->pitch));
    }
}