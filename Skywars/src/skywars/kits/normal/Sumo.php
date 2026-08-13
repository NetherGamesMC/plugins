<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author xBeastMode
 *
 */
declare(strict_types=1);

namespace skywars\kits\normal;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use skywars\kits\Kit;
use skywars\kits\KitIds;

class Sumo extends Kit
{
    public function __construct()
    {
        parent::__construct("Sumo", KitIds::NORMAL_SUMO, [
            VanillaItems::CHAINMAIL_LEGGINGS(),
            VanillaItems::IRON_CHESTPLATE(),
            VanillaItems::STONE_SWORD()
                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1))
        ]);
    }
}