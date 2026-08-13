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

use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class WolfPet extends Animal implements IPetEntity
{
    public const TAG_COLOR = 'Color';

    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::WOLF;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 2, 0);
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->getNetworkProperties()->setLong(EntityMetadataProperties::OWNER_EID, 123456789123456789);
        $this->setColor($nbt->getShort(self::TAG_COLOR, DyeColorIdMap::getInstance()->toId(Utils::getRandomDyeColor())));
    }

    public function setColor(int $color): void
    {
        $this->getNetworkProperties()->setByte(EntityMetadataProperties::COLOR, $color & 0x0f);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.8, 0.6);
    }
}