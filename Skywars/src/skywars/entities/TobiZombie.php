<?php

declare(strict_types=1);

namespace skywars\entities;

use libVanilla\entity\hostile\Zombie;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;

class TobiZombie extends Zombie
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setCanSaveWithChunk(false);
    }

    public function getDefaultItem(): Item
    {
        return VanillaItems::DIAMOND_HOE()
            ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5));
    }

    public function getDrops(): array
    {
        $drops = array_merge($this->getArmorInventory()->getContents(), $this->getInventory()->getContents());

        return [
            $drops[array_rand($drops)]
        ];
    }
}