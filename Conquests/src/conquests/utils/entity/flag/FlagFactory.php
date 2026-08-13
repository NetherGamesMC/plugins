<?php

namespace conquests\utils\entity\flag;

use conquests\CQTeam;
use libminigames\Team;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class FlagFactory
{
    public function __construct(EntityFactory $entityFactory)
    {
        $entityFactory->register(BannerFlagEntity::class, function (World $world, CompoundTag $nbt): BannerFlagEntity {
            return new BannerFlagEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['BannerFlagEntity']);

        $entityFactory->register(CustomFlagEntity::class, function (World $world, CompoundTag $nbt): CustomFlagEntity {
            return new CustomFlagEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CustomFlagEntity']);
    }

    public function getFlag(Location $location, CQTeam $team): BaseFlagEntity
    {
        $nbt = CompoundTag::create()->setByte("Color", DyeColorIdMap::getInstance()->toId($team->getDyeColor()));

        if (($entityId = CosmeticHandler::FLAGS()->get($team->getPlayers(), $team->getId() === Team::RED)) === null) {
            $entity = new BannerFlagEntity($location, $nbt);
        } else {
            $nbt->setString("NetworkTypeId", $entityId);

            $entity = new CustomFlagEntity($location, $nbt);
        }

        $entity->spawnToAll();

        return $entity;
    }
}