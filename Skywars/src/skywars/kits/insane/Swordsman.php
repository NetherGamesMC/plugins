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

namespace skywars\kits\insane;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use skywars\items\SWItems;
use skywars\kits\Kit;
use skywars\kits\KitIds;

class Swordsman extends Kit
{
    /*
     * swordsman resistance potion duration in ticks
     * ((120 seconds + 10 seconds = 130 seconds) * 20 ticks) = 2.10 minutes in ticks
     */
    public const RESISTANCE_POTION_DURATION = (120 + 10) * 20;

    public function __construct()
    {
        parent::__construct("Swordsman", KitIds::INSANE_SWORDSMAN, [
            VanillaItems::IRON_SWORD()
                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1)),
            SWItems::SPLASH_POTION()
                ->setCustomName("Resistance Potion")
                ->addEffect(new EffectInstance(VanillaEffects::RESISTANCE(), self::RESISTANCE_POTION_DURATION, 1))
        ]);
    }

}