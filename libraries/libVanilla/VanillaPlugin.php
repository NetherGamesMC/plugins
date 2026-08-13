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
 * @author Drew, Driesboy
 *
 */
declare(strict_types=1);

namespace libVanilla;

use libVanilla\features\ChestMinecart;
use libVanilla\features\Crossbow;
use libVanilla\features\Elytra;
use libVanilla\features\Enchants;
use libVanilla\features\EnderEye;
use libVanilla\features\Entities;
use libVanilla\features\Feature;
use libVanilla\features\Fireball;
use libVanilla\features\FishingRod;
use libVanilla\features\Hopper;
use libVanilla\features\MissingVanillaBlocks;
use libVanilla\features\NameTag;
use libVanilla\features\Shield;
use libVanilla\features\Tridents;
use libVanilla\features\Worlds;
use pocketmine\utils\RegistryTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static ChestMinecart CHEST_MINECART()
 * @method static NameTag NAME_TAG()
 * @method static EnderEye ENDER_EYE()
 * @method static Crossbow CROSSBOW()
 * @method static Elytra ELYTRA()
 * @method static Enchants ENCHANTS()
 * @method static Entities ENTITIES()
 * @method static Fireball FIREBALL()
 * @method static FishingRod FISHING_ROD()
 * @method static Hopper HOPPER()
 * @method static Shield SHIELD()
 * @method static MissingVanillaBlocks MISSING_VANILLA_BLOCKS()
 * @method static Worlds WORLDS()
 * @method static Tridents TRIDENTS()
 */
class VanillaPlugin
{
    use RegistryTrait;

    protected static function setup(): void
    {
        self::register("chest_minecart", new ChestMinecart());
        self::register("name_tag", new NameTag());
        self::register("ender_eye", new EnderEye());
        self::register("crossbow", new Crossbow());
        self::register("elytra", new Elytra());
        self::register("enchants", new Enchants());
        self::register("entities", new Entities());
        self::register("fireball", new Fireball());
        self::register("fishing_rod", new FishingRod());
        self::register("hopper", new Hopper());
        self::register("shield", new Shield());
        self::register("missing_vanilla_blocks", new MissingVanillaBlocks());
        self::register("worlds", new Worlds());
        self::register("tridents", new Tridents());
    }

    private static function register(string $registryName, Feature $feature): void
    {
        self::_registryRegister($registryName, $feature);
    }

    /**
     * @return Feature[]
     */
    public static function getAll(): array
    {
        return self::_registryGetAll();
    }
}