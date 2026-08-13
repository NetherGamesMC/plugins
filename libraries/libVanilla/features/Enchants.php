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

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemEnchantmentTags as Tags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\lang\KnownTranslationFactory as Locale;
use pocketmine\lang\Translatable;
use pocketmine\plugin\PluginBase;
use ReflectionClass;

class Enchants extends Feature
{
    public const ENCHANT_INPUT = -15;
    public const ENCHANT_MATERIAL = -16;

    /** @var array<int, array{string, array{Translatable, int, int}, array{array<int, string>, array<int, string>}}> */
    public static array $knownEnchantments = [];

    public function setup(PluginBase $plugin): void
    {
        $idMap = EnchantmentIdMap::getInstance();
        $strMap = StringToEnchantmentParser::getInstance();

        if (empty(self::$knownEnchantments)) {
            self::$knownEnchantments = [
                EnchantmentIds::DEPTH_STRIDER => ["depth_strider", [Locale::enchantment_waterWalker(), Rarity::RARE, 3], [[Tags::BOOTS], []]],
                EnchantmentIds::SMITE => ["smite", [Locale::enchantment_damage_undead(), Rarity::COMMON, 5], [[Tags::SWORD], [Tags::AXE]]],
                EnchantmentIds::BANE_OF_ARTHROPODS => ["bane_of_arthropods", [Locale::enchantment_damage_arthropods(), Rarity::COMMON, 5], [[Tags::SWORD], [Tags::AXE]]],
                EnchantmentIds::LOOTING => ["looting", [Locale::enchantment_lootBonus(), Rarity::RARE, 3], [[Tags::SWORD], []]],
                EnchantmentIds::LUCK_OF_THE_SEA => ["luck_of_the_sea", [Locale::enchantment_lootBonusFishing(), Rarity::RARE, 3], [[Tags::FISHING_ROD], []]],
                EnchantmentIds::LURE => ["lure", [Locale::enchantment_fishingSpeed(), Rarity::RARE, 3], [[Tags::FISHING_ROD], []]],
                EnchantmentIds::SOUL_SPEED => ["soul_speed", [Locale::enchantment_soul_speed(), Rarity::MYTHIC, 3], [[Tags::BOOTS], []]],
                EnchantmentIds::LOYALTY => ["loyalty", [Locale::enchantment_tridentLoyalty(), Rarity::UNCOMMON, 3], [[Tags::TRIDENT], []]],
                EnchantmentIds::CHANNELING => ["channeling", [Locale::enchantment_tridentChanneling(), Rarity::MYTHIC, 1], [[Tags::TRIDENT], []]],
                EnchantmentIds::RIPTIDE => ["riptide", [Locale::enchantment_tridentRiptide(), Rarity::RARE, 3], [[Tags::TRIDENT], []]],
                EnchantmentIds::IMPALING => ["impaling", [Locale::enchantment_tridentImpaling(), Rarity::RARE, 5], [[Tags::TRIDENT], []]],
                // TODO: Ask Dylan to add an item flag for crossbows (0x10000?)
                EnchantmentIds::MULTISHOT => ["multishot", [Locale::enchantment_crossbowMultishot(), Rarity::RARE, 1], [[], []]],
                EnchantmentIds::PIERCING => ["piercing", [Locale::enchantment_crossbowPiercing(), Rarity::COMMON, 4], [[], []]],
                EnchantmentIds::QUICK_CHARGE => ["quick_charge", [Locale::enchantment_crossbowQuickCharge(), Rarity::UNCOMMON, 3], [[], []]],
                EnchantmentIds::BINDING => ["binding", [Locale::enchantment_curse_binding(), Rarity::MYTHIC, 1], [[], [Tags::ARMOR, Tags::ELYTRA]]]
            ];
        }

        $registry = AvailableEnchantmentRegistry::getInstance();
        foreach (self::$knownEnchantments as $id => [$name, [$translation, $rarity, $maxLevel], [$primaryTags, $secondaryTags]]) {
            $ench = new Enchantment($translation, $rarity, 0, 0, $maxLevel);
            $strMap->register($name, fn() => $ench);
            $idMap->register($id, $ench);
            $registry->register($ench, $primaryTags, $secondaryTags);
        }
    }

}