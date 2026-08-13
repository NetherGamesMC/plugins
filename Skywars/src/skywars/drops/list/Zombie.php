<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\entities\TobiZombie;
use skywars\SWArena;

class Zombie extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 50;
    }

    public function getPriority(): int
    {
        return self::PRIORITY_ULTRA_LOW;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $zombie = new TobiZombie($block->getLocation());
        $zombie->setNameTag('§r§eTobi');
        $zombie->setNameTagAlwaysVisible();

        $zombie->setSpeed(1.5);

        $zombie->setTargetEntity($player);
        $zombie->entityBaseTick();

        $protection3 = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3);

        $zombie->getArmorInventory()->setContents([
            VanillaItems::DIAMOND_HELMET()->addEnchantment($protection3),
            VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment($protection3),
            VanillaItems::DIAMOND_LEGGINGS()->addEnchantment($protection3),
            VanillaItems::DIAMOND_BOOTS()->addEnchantment($protection3),
        ]);

        $zombie->spawnToAll();
    }
}