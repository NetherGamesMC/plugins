<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\item\WritableBookPage;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\items\SWItems;
use skywars\SWArena;
use skywars\utils\Utils;

class Items extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 40;
    }

    public function getPriority(): int
    {
        return self::PRIORITY_HIGH;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $ironSword = VanillaItems::IRON_SWORD();
        $ironSword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS()));

        $protection1 = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1);
        $unbreaking1 = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);
        $sharpness1 = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1);
        $protection5 = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 5);

        $imAStarCustomName = '§r§eI\'m a star';

        $tyesEssay = VanillaItems::WRITTEN_BOOK();
        $pages = [];

        for ($i = 0; $i < 20; $i++) {
            $pages[] = new WritableBookPage(Utils::randomString(255));
        }

        $tyesEssay->setTitle('Tye\'s Essay');
        $tyesEssay->setAuthor('TyeRCT');
        $tyesEssay->setPages($pages);

        $items = [
            [
                $ironSword
            ],
            [
                VanillaItems::DIAMOND_AXE(),
                VanillaItems::DIAMOND_SHOVEL(),
                VanillaItems::DIAMOND_PICKAXE(),
                VanillaItems::DIAMOND_HOE()
            ],
            [
                VanillaItems::DIAMOND_HELMET()
                    ->addEnchantment($protection1),
                VanillaItems::DIAMOND_BOOTS()
                    ->addEnchantment($protection1),
                VanillaItems::DIAMOND_SWORD()
                    ->addEnchantment($sharpness1)
            ],
            [
                VanillaItems::DIAMOND()->setCount(mt_rand(3, 5)),
                VanillaBlocks::CRAFTING_TABLE()->asItem()
            ],
            [
                VanillaItems::IRON_INGOT()->setCount(mt_rand(3, 5)),
                VanillaBlocks::CRAFTING_TABLE()->asItem()
            ],
            [
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_STRENGTH)
            ],
            [
                VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION)
            ],
            [
                VanillaItems::SPLASH_POTION()->setType(PotionType::SWIFTNESS)
            ],
            [
                VanillaItems::ENDER_PEARL()->setCount(mt_rand(1, 3))
            ],
            [
                VanillaItems::DIAMOND_LEGGINGS()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->setCustomName('§r§l§cPatrick Star\'s §fPants')
            ],
            [
                VanillaItems::GOLDEN_HELMET()
                    ->addEnchantment($unbreaking1)
                    ->setCustomName($imAStarCustomName),
                VanillaItems::GOLDEN_CHESTPLATE()
                    ->addEnchantment($unbreaking1)
                    ->setCustomName($imAStarCustomName),
                VanillaItems::GOLDEN_LEGGINGS()
                    ->addEnchantment($unbreaking1)
                    ->setCustomName($imAStarCustomName),
                VanillaItems::GOLDEN_BOOTS()
                    ->addEnchantment($unbreaking1)
                    ->setCustomName($imAStarCustomName),
                VanillaItems::GOLDEN_SWORD()
                    ->addEnchantment($unbreaking1)
                    ->setCustomName($imAStarCustomName)
            ],
            [
                VanillaItems::DIAMOND_CHESTPLATE()
                    ->addEnchantment($protection1)
            ],
            [
                SWItems::GRAPPLING_ROD()
            ],
            [
                VanillaItems::GOLDEN_SWORD()
                    ->addEnchantment($sharpness1)
                    ->setCustomName('§r§l§eGodly Dagger')
            ],
            [
                VanillaItems::POTATO()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 2))
                    ->setCustomName('§r§l§cHot Potato')
            ],
            [
                VanillaItems::LEATHER_TUNIC()
                    ->addEnchantment($protection5)
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::THORNS(), 2))
                    ->setCustomName('§r§lDries §rvery fast')
            ],
            [
                VanillaItems::APPLE()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1))
                    ->setCustomName('§r§fCallum\'s Apple')
            ],
            [
                $tyesEssay
            ],
            [
                VanillaItems::DIAMOND_SWORD()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2))
                    ->setCustomName('§r§cQuoia\'s Blade')
            ],
            [
                VanillaItems::BOW()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 2))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1))
                    ->setCustomName('§r§aKanine\'s Bow'),
                VanillaItems::ARROW()->setCount(32)
            ],
            [
                VanillaBlocks::TORCH()
                    ->asItem()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 10))
                    ->setCustomName('§r§eHoly Chalice')
            ],
            [
                VanillaItems::DIAMOND_CHESTPLATE()
                    ->addEnchantment($protection1),
                VanillaItems::DIAMOND_LEGGINGS()
                    ->addEnchantment($protection1),
                VanillaItems::DIAMOND_SWORD()
                    ->addEnchantment($sharpness1)
            ],
            [
                VanillaItems::LEATHER_TUNIC()
                    ->setCustomColor(Color::fromRGB(0xFFFFFF))
                    ->addEnchantment($protection5)
                    ->setCustomName('§r§fCasper\'s Spooky Suit')
            ],
            [
                VanillaItems::COOKIE()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 10))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1))
                    ->setCustomName('§r§6Matcracker\'s Cookie')
            ],
            [
                VanillaBlocks::POPPY()->asItem()
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 2))
                    ->setCustomName('§r§cMomma\'s rose')
            ]
        ];

        $items = $items[array_rand($items)];
        foreach ($items as $item) {
            $player->getWorld()->dropItem($block->getLocation(), $item);
        }
    }
}