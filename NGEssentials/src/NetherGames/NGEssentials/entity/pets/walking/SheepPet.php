<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Drew, Driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\passive\Sheep;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class SheepPet extends Sheep implements IPetEntity
{
    public const TAG_COLOR = 'Color';

    use WalkingPetTrait;

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 2.2, 0);
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $dyeMap = DyeColorIdMap::getInstance();

        $this->setColor($dyeMap->fromId($nbt->getShort(self::TAG_COLOR, $dyeMap->toId(Utils::getRandomDyeColor()))));
    }
}