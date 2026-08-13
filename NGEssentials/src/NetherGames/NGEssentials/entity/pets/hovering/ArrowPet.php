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

use libPhysX\internal\Rotation;
use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\EntityBase;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

class ArrowPet extends EntityBase implements IPetEntity, AIEntity
{
    use HoveringNoCollisionTrait {
        HoveringNoCollisionTrait::initPetData as baseInitPetData;
    }

    public const TAG_CRITICAL = 'IsCritical';

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ARROW;
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->baseInitPetData($nbt);
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::CRITICAL, (bool)$nbt->getByte(self::TAG_CRITICAL, 0));
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.25, 0.25);
    }

    protected function getClientSideRotation(): Rotation
    {
        return new Rotation(($this->location->yaw > 180 ? 360 : 0) - $this->location->yaw, -$this->location->pitch);
    }
}