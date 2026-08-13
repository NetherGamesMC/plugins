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

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class Fireball extends Feature
{
    protected function setup(PluginBase $plugin): void
    {
        $entityFactory = EntityFactory::getInstance();
        $entityFactory->register(\libVanilla\entity\object\Fireball::class, function (World $world, CompoundTag $nbt): \libVanilla\entity\object\Fireball {
            return new \libVanilla\entity\object\Fireball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['Fireball', 'minecraft:fireball']);
    }
}