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

namespace bedwars\shops;

use bedwars\BWTeam;
use bedwars\shops\menu\UpgraderMenu;
use bedwars\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\RegistryTrait;
use pocketmine\utils\TextFormat;

/**
 * @method static self ARMOR()
 * @method static self BLAST_PROTECTION()
 * @method static self DRAGON_BUFF()
 * @method static self FORGE()
 * @method static self HEALTH_POOL()
 * @method static self HEART_STEALER()
 * @method static self MINER()
 * @method static self SOFT_BOOTS()
 * @method static self SWORDS()
 */
final class Upgrade
{
    use RegistryTrait;

    protected static function setup(): void
    {
        self::register(
            registryName: "swords",
            name: "Sharpened Swords",
            description: "Your team permanently gains Sharpness on all swords & axes!",
            item: VanillaItems::IRON_SWORD(),
            iconUrl: "textures/items/iron_sword",
            tiers: [
                1 => new UpgradeTier(
                    description: "Your team permanently gains Sharpness I on all swords & axes!",
                    cost: 4,
                    teamCost: 8,
                    tieredText: "Tier 1: Sharpness I" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                ),
                2 => new UpgradeTier(
                    description: "Your team permanently gains Sharpness II on all swords & axes!",
                    cost: 10,
                    teamCost: 15,
                    tieredText: "Tier 1: Sharpness II" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                )
            ]
        );
        self::register(
            registryName: "armor",
            name: "Reinforced Armor",
            description: "Your team permanently gains Protection on all armor pieces!",
            item: VanillaItems::IRON_CHESTPLATE(),
            iconUrl: "textures/items/iron_chestplate",
            tiers: [
                1 => new UpgradeTier(
                    description: "Your team permanently gains Protection I on all armor pieces!",
                    cost: 2,
                    teamCost: 5,
                    tieredText: "Tier 1: Protection I" . TextFormat::GRAY . ", " . TextFormat::AQUA . "%price% Diamonds"
                ),
                2 => new UpgradeTier(
                    description: "Your team permanently gains Protection II on all armor pieces!",
                    cost: 4,
                    teamCost: 10,
                    tieredText: "Tier 2: Protection II" . TextFormat::GRAY . ", " . TextFormat::AQUA . "%price% Diamonds"
                ),
                3 => new UpgradeTier(
                    description: "Your team permanently gains Protection III on all armor pieces!",
                    cost: 8,
                    teamCost: 12,
                    tieredText: "Tier 3: Protection III" . TextFormat::GRAY . ", " . TextFormat::AQUA . "%price% Diamonds"
                ),
                4 => new UpgradeTier(
                    description: "Your team permanently gains Protection IV on all armor pieces!",
                    cost: 12,
                    teamCost: 15,
                    tieredText: "Tier 4: Protection IV" . TextFormat::GRAY . ", " . TextFormat::AQUA . "%price% Diamonds"
                ),
            ]
        );
        self::register(
            registryName: "miner",
            name: "Maniac Miner",
            description: "All players on your team permanently gain Haste.",
            item: VanillaItems::GOLDEN_PICKAXE(),
            iconUrl: "textures/items/gold_pickaxe",
            tiers: [
                1 => new UpgradeTier(
                    description: "All players on your team permanently gain Haste I.",
                    cost: 2,
                    teamCost: 4,
                    tieredText: "Tier 1: Haste I" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                ),
                2 => new UpgradeTier(
                    description: "All players on your team permanently gain Haste II.",
                    cost: 4,
                    teamCost: 6,
                    tieredText: "Tier 2: Haste II" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                )
            ]
        );
        self::register(
            registryName: "forge",
            name: "Forge",
            description: "Upgrade resource spawning on your island.",
            item: VanillaBlocks::FURNACE()->asItem(),
            iconUrl: "textures/blocks/furnace_front_off",
            tiers: [
                1 => new UpgradeTier(
                    description: "Increases the spawn rate of Iron and Gold by 50%",
                    cost: 2,
                    teamCost: 4,
                    customName: "Iron Forge",
                    tieredText: "Tier 1: +50% Resources" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                ),
                2 => new UpgradeTier(
                    description: "Increases the spawn rate of Gold by 100%",
                    cost: 4,
                    teamCost: 8,
                    customName: "Golden Forge",
                    tieredText: "Tier 2: +100% Resources" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . "  Diamonds"
                ),
                3 => new UpgradeTier(
                    description: "Activates the Emerald generator in your team's Forge.",
                    cost: 6,
                    teamCost: 12,
                    customName: "Emerald Forge",
                    tieredText: "Tier 3: Spawn emeralds" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                ),
                4 => new UpgradeTier(
                    description: "+200% Resources",
                    cost: 8,
                    teamCost: 16,
                    customName: "Molten Forge",
                    tieredText: "Tier 4: +200% Resources" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                )
            ]
        );
        self::register(
            registryName: "health_pool",
            name: "Health Pool",
            description: "Creates a Regeneration field around your base!",
            item: VanillaBlocks::BEACON()->asItem(),
            iconUrl: "textures/blocks/beacon",
            tiers: [1 => new UpgradeTier(description: "Creates a Regeneration field around your base!", cost: 1, teamCost: 3)]
        );
        self::register(
            registryName: "heart_stealer",
            name: "Heart Stealer",
            description: "Your team will get a chance of stealing the amount of hearts they inflicted on the enemy!",
            item: VanillaItems::SPLASH_POTION()->setType(PotionType::HEALING),
            iconUrl: "textures/items/potion_bottle_splash_heal",
            tiers: [
                1 => new UpgradeTier(description: "Increases the chance to 12%!", cost: 5, teamCost: 10, customName: "Heart Stealer", tieredText: "Tier 1: 12% Chance" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"),
                2 => new UpgradeTier(description: "Increases the chance to 20%!", cost: 8, teamCost: 14, customName: "Heart Stealer", tieredText: "Tier 2: 20% Chance" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"),
            ]
        );
        self::register(
            registryName: "dragon_buff",
            name: "Dragon Buff",
            description: "Your team will have 2 dragons instead of 1 during deathmatch!",
            item: VanillaBlocks::DRAGON_EGG()->asItem(),
            iconUrl: "textures/blocks/dragon_egg",
            tiers: [1 => new UpgradeTier(description: "Your team will have 2 dragons instead of 1 during deathmatch!", cost: 5, teamCost: 5)]
        );
        self::register(
            registryName: "soft_boots",
            name: "Soft Boots",
            description: "Your team will take less fall damage!.",
            item: VanillaItems::LEATHER_BOOTS()->setCustomColor(DyeColor::WHITE()->getRgbValue()),
            iconUrl: "textures/items/leather_boots",
            tiers: [
                1 => new UpgradeTier(
                    description: "All players on your team have Feather Falling I enchanted boots.",
                    cost: 2,
                    teamCost: 3,
                    tieredText: "Tier 1: Feather Falling I" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                ),
                2 => new UpgradeTier(
                    description: "All players on your team have Feather Falling II enchanted boots.",
                    cost: 3,
                    teamCost: 4,
                    tieredText: "Tier 2: Feather Falling II" . TextFormat::GRAY . ", " . TextFormat::AQUA . UpgraderMenu::PRICE_SEARCH_PATTERN . " Diamonds"
                )
            ]
        );
        self::register(
            registryName: "blast_protection",
            name: "Blast Protection",
            description: "Your team will take less explosion damage!.",
            item: VanillaBlocks::TNT()->asItem(),
            iconUrl: "textures/blocks/tnt_side",
            tiers: [1 => new UpgradeTier(description: "Applies Blast Protection II on all armour peices!", cost: 2, teamCost: 3)]
        );
    }

    /** @var array<string, self> */
    private static array $nameAliasMapping = [];

    /**
     * @param array<UpgradeTier> $tiers
     */
    protected static function register(string $registryName, string $name, string $description, Item $item, string $iconUrl, array $tiers): void
    {
        $item = new self($name, $description, $item, $iconUrl, $tiers);
        self::_registryRegister($registryName, $item);
        self::$nameAliasMapping[strtolower($name)] = $item;
    }

    /**
     * @return array<self>
     */
    public static function getAll(): array
    {
        /** @var array<self> $result */
        $result = self::_registryGetAll();
        return $result;
    }

    public static function fromName(string $name): ?self
    {
        return self::$nameAliasMapping[strtolower($name)] ?? null;
    }

    /**
     * @param Item $item
     * @param array<UpgradeTier> $tiers
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        private readonly Item  $item,
        public readonly string $iconUrl,
        public readonly array  $tiers
    )
    {
    }

    public function asItem(): Item
    {
        return clone $this->item;
    }

    /**
     * Returns a formatted name used for forms
     * This name consists of the upgrade name (or tier custom name, if applicable) and the level as a roman numeral (e.g. "Reinforced Armor II")
     */
    public function getFormattedName(int $level = 0): string
    {
        $tier = $this->getTier($level);
        return match (true) {
            $tier !== null && $tier->hasCustomName() => $tier->customName,
            $this->hasTiers() => $this->name . " " . Utils::getRomanNumber($level),
            default => $this->name
        };
    }

    /**
     * Attempts to locate the tier for the given level.
     */
    public function getTier(int $level): ?UpgradeTier
    {
        return $this->tiers[$level] ?? null;
    }

    /**
     * Returns the next tier of the upgrade, or null if the upgrade is at the highest tier
     */
    public function getNextTier(int $level): ?UpgradeTier
    {
        return $this->tiers[$level + 1] ?? null;
    }

    /**
     * Returns true if there is more than one tier of upgrades available.
     */
    public function hasTiers(): bool
    {
        return count($this->tiers) > 1;
    }

    /**
     * Returns true if the item can be upgraded to the next tier.
     */
    public function hasUpgrade(int $level): bool
    {
        return isset($this->tiers[$level + 1]);
    }

    /**
     * Handles the item-adding logic and calls the associated `onPurchase` closure
     * Returns an error message or null if the purchase was successful
     */
    public function handlePurchase(Player $player, BWTeam $team, ?int $multiplier = null): ?string
    {
        return null;
    }
}