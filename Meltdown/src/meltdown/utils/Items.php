<?php

namespace meltdown\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

abstract class Items extends \libminigames\utils\Items{
    public static function getIceBoots() : Item{
        return VanillaItems::IRON_BOOTS()->setCustomName("§r§bIce Boots");
    }

    public static function getSlipperyBoots() : Item{
        return VanillaItems::LEATHER_BOOTS()->setCustomName("§r§bSlippery Boots");
    }

    public static function getSnowBomb() : Item{
        return VanillaItems::SNOWBALL()->setCustomName("§r§bSnow Bomb");
    }
}