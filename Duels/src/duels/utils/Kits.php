<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace duels\utils;

use duels\DuelsArena;
use libVanilla\LibVanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Limits;

class Kits
{
    public static function giveKit(Player $player, int $kitId): void
    {
        /** @var EffectInstance[] $effects */
        $effects = [];
        $armorContents = [];
        $contents = [];

        switch ($kitId) {
            case DuelsArena::TYPE_NORMAL:
                $armorContents = [
                    VanillaItems::IRON_HELMET(),
                    VanillaItems::IRON_CHESTPLATE(),
                    VanillaItems::IRON_LEGGINGS(),
                    VanillaItems::IRON_BOOTS()
                ];
                $contents = [
                    VanillaItems::IRON_SWORD(),
                    (function () {
                        $rod = LibVanillaItems::FISHING_ROD();
                        $rod->setCombatRod(true);
                        return $rod;
                    })(),
                    VanillaItems::BOW(),
                    VanillaItems::FLINT_AND_STEEL()->setDamage(VanillaItems::FLINT_AND_STEEL()->getMaxDurability() - 15),
                    VanillaItems::ARROW()->setCount(5)
                ];
                break;
            case DuelsArena::TYPE_IRON_SOUP:
                $effects[] = new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 1, true, true);
                $armorContents = [
                    VanillaItems::IRON_HELMET(),
                    VanillaItems::IRON_CHESTPLATE(),
                    VanillaItems::IRON_LEGGINGS(),
                    VanillaItems::IRON_BOOTS()
                ];
                $contents = [
                    VanillaItems::DIAMOND_SWORD(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                    VanillaItems::MUSHROOM_STEW(),
                ];
                break;
            case DuelsArena::TYPE_BOW:
                $contents = [
                    VanillaItems::BOW(),
                    VanillaItems::ARROW(),
                ];
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1));
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));
                break;
            case DuelsArena::TYPE_INSANE:
                $armorContents = [
                    VanillaItems::LEATHER_CAP(),
                    VanillaItems::LEATHER_TUNIC(),
                    VanillaItems::LEATHER_PANTS(),
                    VanillaItems::LEATHER_BOOTS()
                ];
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $contents = [
                    VanillaItems::GOLDEN_SWORD(),
                    VanillaItems::BOW(),
                    VanillaItems::ARROW()->setCount(12),
                ];
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 4));
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $contents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
                break;
            case DuelsArena::TYPE_OVERPOWERED:
                $armorContents = [
                    VanillaItems::DIAMOND_HELMET(),
                    VanillaItems::DIAMOND_CHESTPLATE(),
                    VanillaItems::DIAMOND_LEGGINGS(),
                    VanillaItems::DIAMOND_BOOTS()
                ];
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
                $contents = [
                    VanillaItems::DIAMOND_SWORD(),
                    (function () {
                        $rod = LibVanillaItems::FISHING_ROD();
                        $rod->setCombatRod(true);
                        return $rod;
                    })(),
                    VanillaItems::BOW(),
                    VanillaItems::FLINT_AND_STEEL(),
                    VanillaItems::GOLDEN_APPLE()->setCount(6),
                    VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION),
                    VanillaItems::SPLASH_POTION()->setType(PotionType::LONG_SWIFTNESS),
                    VanillaItems::ARROW()->setCount(20)
                ];
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5));
                $contents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 4));
                break;
            case DuelsArena::TYPE_GAPPLE:
                $armorContents = [
                    VanillaItems::AIR(),
                    VanillaItems::IRON_CHESTPLATE(),
                    VanillaItems::AIR(),
                    VanillaItems::AIR(),
                ];
                $contents = [
                    VanillaItems::WOODEN_SWORD(),
                    VanillaItems::GOLDEN_APPLE()->setCount(64)
                ];
                break;
            case DuelsArena::TYPE_NO_DEBUFF:
                $armorContents = [
                    VanillaItems::DIAMOND_HELMET(),
                    VanillaItems::DIAMOND_CHESTPLATE(),
                    VanillaItems::DIAMOND_LEGGINGS(),
                    VanillaItems::DIAMOND_BOOTS()
                ];
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $contents = [
                    VanillaItems::DIAMOND_SWORD(),
                    VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING),
                    VanillaItems::STEAK()->setCount(16),
                    VanillaItems::POTION()->setType(PotionType::LONG_FIRE_RESISTANCE),
                    VanillaItems::POTION()->setType(PotionType::LONG_SWIFTNESS)->setCount(4)
                ];
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
                $contents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1));
                break;
            case DuelsArena::TYPE_COMBO:
                $armorContents = [
                    VanillaItems::DIAMOND_HELMET(),
                    VanillaItems::DIAMOND_CHESTPLATE(),
                    VanillaItems::DIAMOND_LEGGINGS(),
                    VanillaItems::DIAMOND_BOOTS()
                ];
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), 2));
                $contents = [
                    VanillaItems::IRON_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)),
                    VanillaItems::GOLDEN_APPLE()->setCount(32),
                ];
                break;
            case DuelsArena::TYPE_SUMO:
                $effects = [
                    new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX, 10, false, true),
                    new EffectInstance(VanillaEffects::WATER_BREATHING(), Limits::INT32_MAX, 1, false, true)
                ];
                break;
            case DuelsArena::TYPE_BUILDUHC:
                $armorContents = [
                    VanillaItems::DIAMOND_HELMET(),
                    VanillaItems::DIAMOND_CHESTPLATE(),
                    VanillaItems::DIAMOND_LEGGINGS(),
                    VanillaItems::DIAMOND_BOOTS()
                ];
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[1]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[2]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                $armorContents[3]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
                $contents = [
                    VanillaItems::DIAMOND_SWORD()
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1))
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)),
                    VanillaItems::BOW()
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)),
                    VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64),
                    (function () {
                        $rod = LibVanillaItems::FISHING_ROD();
                        $rod->setCombatRod(true);
                        return $rod;
                    })(),
                    VanillaItems::GOLDEN_APPLE()->setCount(5),
                    VanillaItems::IRON_AXE()
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 5))
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2)),
                    VanillaItems::DIAMOND_PICKAXE()
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 5)),
                    VanillaItems::WATER_BUCKET(),
                    VanillaItems::LAVA_BUCKET(),
                    VanillaItems::ARROW()->setCount(8),
                    VanillaItems::WATER_BUCKET(),
                    VanillaItems::LAVA_BUCKET(),
                    VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64),
                ];
                break;
        }

        $player->getInventory()->setContents($contents);
        $player->getArmorInventory()->setContents($armorContents);
        $player->getInventory()->setHeldItemIndex(0);

        $player->getInventory()->setContents($contents);
        $player->getArmorInventory()->setContents($armorContents);
        $player->getInventory()->setHeldItemIndex(0);

        if ($kitId === DuelsArena::TYPE_NO_DEBUFF) {
            $inv = $player->getInventory();
            $size = $inv->getSize();
            $splash = VanillaItems::SPLASH_POTION();
            $splash->setType(PotionType::STRONG_HEALING);
            for ($i = 0; $i < $size; $i++) {
                if ($inv->getItem($i)->isNull()) {
                    $inv->setItem($i, $splash);
                }
            }
        }

        foreach ($effects as $effect) {
            $player->getEffects()->add($effect);
        }
    }
}
