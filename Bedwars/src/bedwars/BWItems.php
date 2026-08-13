<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars;

use libVanilla\item\Fireball;
use libVanilla\LibVanillaItems;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\MilkBucket;
use pocketmine\item\Potion;
use pocketmine\item\PotionType;
use pocketmine\item\Snowball;
use pocketmine\item\VanillaItems;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\utils\TextFormat;

/**
 * @method static Fireball FIREBALL()
 * @method static Item LEVITATION_HELMET()
 * @method static ItemBlock POPUP_TOWER()
 * @method static MilkBucket MAGIC_MILK()
 * @method static Potion HASTE_POTION()
 * @method static Potion INVISIBILITY_POTION()
 * @method static Potion LEVITATION_POTION()
 * @method static Potion STRENGTH_POTION()
 * @method static Potion STRONG_LEAPING_POTION()
 * @method static Potion SWIFTNESS_POTION()
 * @method static Snowball BEDBUG_SNOWBALL()
 * @method static Item DEFENDER_EGG()
 * @method static Item SKELETON_ARMY_EGG()
 */
final class BWItems
{
    use CloningRegistryTrait;

    public const string TAG_BLOCKS = "Blocks";
    public const int LEVITATION_DURATION = 7;

    protected static function setup(): void
    {
        self::register("invisibility_potion", VanillaItems::POTION()->setType(PotionType::INVISIBILITY)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Invisibility Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . "30 seconds",
            TextFormat::RED . TextFormat::BOLD . TextFormat::UNDERLINE . "Conditions:" . TextFormat::RESET,
            TextFormat::RED . " - Must not be levitating",
        ]));
        self::register("strong_leaping_potion", VanillaItems::POTION()->setType(PotionType::STRONG_LEAPING)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Leaping Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . "45 seconds"
        ]));
        self::register("swiftness_potion", VanillaItems::POTION()->setType(PotionType::SWIFTNESS)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Swiftness Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . "45 seconds"
        ]));
        self::register("strength_potion", VanillaItems::POTION()->setType(PotionType::STRENGTH)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Strength Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . "30 seconds"
        ]));
        self::register("haste_potion", VanillaItems::POTION()->setType(PotionType::NIGHT_VISION)->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Haste Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . "30 seconds"
        ]));
        self::register("levitation_potion", VanillaItems::POTION()->setType(PotionType::AWKWARD())->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Levitation Potion")->setLore([
            TextFormat::RESET . TextFormat::GRAY . self::LEVITATION_DURATION . " seconds",
            TextFormat::RED . TextFormat::BOLD . TextFormat::UNDERLINE . "Conditions:" . TextFormat::RESET,
            TextFormat::RED . " - Must not be already levitating",
            TextFormat::RED . " - Must not be invisible",
        ]));
        self::register("magic_milk", VanillaItems::MILK_BUCKET()->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Magic Milk"));
        self::register("defender_egg", CustomItemRegistry::IRON_GOLEM_SPAWN_EGG()->setCustomName(TextFormat::RESET . TextFormat::RED . "Dream Defender"));
        self::register("skeleton_army_egg", CustomItemRegistry::SKELETON_SPAWN_EGG()->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Skeleton Army"));
        self::register("bedbug_snowball", VanillaItems::SNOWBALL()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Bedbug"));
        self::register("bridge_egg", CustomItemRegistry::TURTLE_SPAWN_EGG()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Bridge Egg"));
        self::register("popup_tower", VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Compact Pop-up Tower"));
        self::register("fireball", LibVanillaItems::FIREBALL());
        self::register("levitation_helmet", VanillaItems::GOLDEN_HELMET()->setCustomName(TextFormat::RESET . TextFormat::DARK_PURPLE . "Levitation Helmet"));
    }

    public static function BRIDGE_EGG(int $blocks = 32): Item
    {
        $item = CustomItemRegistry::TURTLE_SPAWN_EGG()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Bridge Egg");
        $item->getNamedTag()->setInt(self::TAG_BLOCKS, $blocks);
        $item->setLore([TextFormat::RESET . TextFormat::GRAY . "Blocks: " . TextFormat::GREEN . $blocks]);

        return $item;
    }

    protected static function register(string $name, Item $item): void
    {
        self::_registryRegister($name, $item);
    }
}