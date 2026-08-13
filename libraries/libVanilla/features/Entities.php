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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\features;

use libVanilla\entity\registry\ActorList;
use libVanilla\entity\registry\ActorRegistry;
use libVanilla\item\SpawnEgg;
use libVanilla\listener\EntityListener;
use libVanilla\VanillaPlugin;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\world\World;

class Entities extends Feature
{
    protected function setup(PluginBase $plugin): void
    {
        $entityFactory = EntityFactory::getInstance();
        $stringToItemParser = StringToItemParser::getInstance();

        /** @var ActorList $mob */
        foreach (ActorRegistry::getAll() as $mob) {
            $entityFactory->register($mob->getClass(), function (World $world, CompoundTag $nbt) use ($mob): Entity {
                $class = $mob->getClass();
                return new $class(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            }, [$mob->getName(), $mob->getNewId()]);

            $minecraftId = str_replace(" ", "_", strtolower($mob->getName())) . "_spawn_egg";
            $egg = new SpawnEgg(
                new ItemIdentifier(ItemTypeIds::newId()), "{$mob->getName()} Spawn Egg", $mob->getClass()
            );
            GlobalItemDataHandlers::getDeserializer()->map($minecraftId, fn() => clone $egg);
            GlobalItemDataHandlers::getSerializer()->map($egg, fn() => new SavedItemData($minecraftId));
            $stringToItemParser->override($minecraftId, fn() => $egg);
        }
        VanillaPlugin::FIREBALL()->register($plugin);

        $plugin->getServer()->getPluginManager()->registerEvents(new EntityListener(), $plugin);
    }
}