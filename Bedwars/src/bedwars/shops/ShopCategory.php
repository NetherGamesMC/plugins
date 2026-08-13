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

namespace bedwars\shops;

use bedwars\BWItems;
use bedwars\BWTeam;
use bedwars\shops\cost\CostType;
use bedwars\shops\cost\ItemCost;
use bedwars\utils\Items;
use bedwars\utils\Utils;
use InvalidArgumentException;
use libminigames\utils\AutoUpgrader;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FireExtinguishSound;
use function array_filter;
use function array_key_first;
use function assert;
use function is_array;

/**
 * @method static self ARMOR()
 * @method static self BLOCKS()
 * @method static self DEFENSE()
 * @method static self POTIONS()
 * @method static self TOOLS()
 * @method static self UTILITY()
 * @method static self WEAPONS()
 */
final class ShopCategory
{
    use CloningRegistryTrait;

    protected static function setup(): void
    {
        self::register(
            name: "Blocks",
            displayItem: VanillaBlocks::STAINED_CLAY()->asItem(),
            iconPath: "textures/blocks/hardened_clay",
            items: [
                new SlideableShopItem(
                    name: "Wool",
                    value: VanillaBlocks::WOOL()->asItem()->setCount(16),
                    iconPath: "textures/blocks/wool_colored_white",
                    cost: new ItemCost(CostType::IRON(), 4),
                    description: "Great for bridging across islands.",
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var Wool $block */
                        $block = $item->getBlock();
                        return Utils::applyDye($block, $team->getDyeColor())->asItem()->setCount($item->getCount());
                    }
                ),
                new SlideableShopItem(
                    name: "Ladder",
                    value: VanillaBlocks::LADDER()->asItem()->setCount(16),
                    iconPath: "textures/blocks/ladder",
                    cost: new ItemCost(CostType::IRON(), 4),
                    description: "Useful to save cats stuck in trees."
                ),
                new SlideableShopItem(
                    name: "Stained Clay",
                    value: VanillaBlocks::STAINED_CLAY()->asItem()->setCount(12),
                    iconPath: "textures/blocks/hardened_clay",
                    cost: new ItemCost(CostType::IRON(), 8),
                    description: "Basic block to defend your bed.",
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var StainedHardenedClay $block */
                        $block = $item->getBlock();
                        return Utils::applyDye($block, $team->getDyeColor())->asItem()->setCount($item->getCount());
                    }
                ),
                new SlideableShopItem(
                    name: "Blast-Proof Glass",
                    value: VanillaBlocks::STAINED_GLASS()->asItem()->setCount(4),
                    iconPath: "textures/blocks/glass",
                    cost: new ItemCost(CostType::IRON(), 8),
                    description: "Immune to explosions",
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var StainedGlass $block */
                        $block = $item->getBlock();
                        return Utils::applyDye($block, $team->getDyeColor())->asItem()->setCount($item->getCount());
                    }
                ),
                new SlideableShopItem(
                    name: "End Stone",
                    value: VanillaBlocks::END_STONE()->asItem()->setCount(12),
                    iconPath: "textures/blocks/end_stone",
                    cost: new ItemCost(CostType::IRON(), 24),
                    description: "Solid block to defend your bed. Immune to fireballs."
                ),
                new SlideableShopItem(
                    name: "Oak Planks",
                    value: VanillaBlocks::OAK_PLANKS()->asItem()->setCount(16),
                    iconPath: "textures/blocks/planks_oak",
                    cost: new ItemCost(CostType::GOLD(), 4),
                    description: "Good block to defend your bed. Strong against pickaxes.",
                ),
                new SlideableShopItem(
                    name: "Obsidian",
                    value: VanillaBlocks::OBSIDIAN()->asItem()->setCount(4),
                    iconPath: "textures/blocks/obsidian",
                    cost: new ItemCost(CostType::EMERALD(), 4),
                    description: "Extreme protection for your bed."
                ),
            ]
        );
        self::register(
            name: "Weapons",
            displayItem: VanillaItems::GOLDEN_SWORD(),
            iconPath: "textures/items/gold_sword",
            items: [
                new ShopItem(
                    name: "Stone Sword",
                    value: VanillaItems::STONE_SWORD(),
                    iconPath: "textures/items/stone_sword",
                    cost: new ItemCost(CostType::IRON(), 10),
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                        }
                        return $item;
                    },
                    replaceFn: function (Item $item, Player $player, BWTeam $team): void {
                        if (Utils::replaceItem($player, VanillaItems::WOODEN_SWORD(), $item)) {
                            return;
                        }

                        $player->getInventory()->addItem(AutoUpgrader::getInstance()->upgradeItem($player, $item));
                    },
                ),
                new ShopItem(
                    name: "Iron Sword",
                    value: VanillaItems::IRON_SWORD(),
                    iconPath: "textures/items/iron_sword",
                    cost: new ItemCost(CostType::GOLD(), 7),
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                        }
                        return $item;
                    },
                    replaceFn: function (Item $item, Player $player, BWTeam $team): void {
                        if (Utils::replaceItem($player, VanillaItems::WOODEN_SWORD(), $item)) {
                            return;
                        }

                        $player->getInventory()->addItem(AutoUpgrader::getInstance()->upgradeItem($player, $item));
                    },
                ),
                new ShopItem(
                    name: "Diamond Sword",
                    value: VanillaItems::DIAMOND_SWORD(),
                    iconPath: "textures/items/diamond_sword",
                    cost: new ItemCost(
                        type: CostType::EMERALD(),
                        amount: 4,
                        teamAmount: 3
                    ),
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                        }
                        return $item;
                    },
                    replaceFn: function (Item $item, Player $player, BWTeam $team): void {
                        if (Utils::replaceItem($player, VanillaItems::WOODEN_SWORD(), $item)) {
                            return;
                        }

                        $player->getInventory()->addItem(AutoUpgrader::getInstance()->upgradeItem($player, $item));
                    },
                ),
                new ShopItem(
                    name: "Knockback Stick",
                    value: VanillaItems::STICK()->addEnchantment(
                        new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1)
                    ),
                    iconPath: "textures/items/stick",
                    cost: new ItemCost(CostType::GOLD(), 5),
                ),
                new SlideableShopItem(
                    name: "Arrow",
                    value: VanillaItems::ARROW()->setCount(8),
                    iconPath: "textures/items/arrow",
                    cost: new ItemCost(CostType::GOLD(), 2),
                ),
                new ShopItem(
                    name: "Bow (Not Enchanted)",
                    value: VanillaItems::BOW(),
                    iconPath: "textures/items/bow_standby",
                    cost: new ItemCost(CostType::GOLD(), 12),
                ),
                new ShopItem(
                    name: "Bow (Power I)",
                    value: VanillaItems::BOW()->addEnchantment(
                        new EnchantmentInstance(VanillaEnchantments::POWER(), 1)
                    ),
                    iconPath: "textures/items/bow_standby",
                    cost: new ItemCost(CostType::GOLD(), 24),
                ),
                new ShopItem(
                    name: "Bow (Power I, Punch I)",
                    value: VanillaItems::BOW()
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1))
                        ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1)),
                    iconPath: "textures/items/bow_standby",
                    cost: new ItemCost(CostType::EMERALD(), 6),
                ),
            ]
        );
        self::register(
            name: "Armor",
            displayItem: VanillaItems::CHAINMAIL_BOOTS(),
            iconPath: "textures/items/chainmail_boots",
            items: [
                new ShopItem(
                    name: "Permanent Chainmail Armor",
                    value: [
                        ArmorInventory::SLOT_LEGS => VanillaItems::CHAINMAIL_LEGGINGS(),
                        ArmorInventory::SLOT_FEET => VanillaItems::CHAINMAIL_BOOTS()
                    ],
                    iconPath: "textures/items/chainmail_boots",
                    cost: new ItemCost(CostType::IRON(), 20),
                    purchaseValidatorFn: function (ShopItem $item, Player $player, BWTeam $team): ?string {
                        assert(is_array($item->value));

                        $armorDifference = Utils::getArmorDifference($player->getArmorInventory()->getLeggings(), $item->value[ArmorInventory::SLOT_LEGS]);

                        if ($armorDifference >= 0) {
                            $player->broadcastSound(new FireExtinguishSound(), [$player]);
                            return TextFormat::GREEN . "You already have " . ($armorDifference === 0 ? 'this' : 'better') . " armor!";
                        }

                        return null;
                    },
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var Armor $item */
                        if (($level = $team->getUpgradeLevel(Upgrade::ARMOR())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
                        }
                        if ($item->getArmorSlot() === ArmorInventory::SLOT_FEET && ($level = $team->getUpgradeLevel(Upgrade::SOFT_BOOTS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), $level));
                        }
                        return $item;
                    },
                    onPurchase: function (ShopItem $item, Player $player, BWTeam $team): void {
                        $value = $item->getValue();
                        assert(is_array($value));
                        $team->setPermanent(
                            player: $player,
                            key: BWTeam::ARMOR,
                            value: $value
                        );
                    },
                    slot: ShopInventorySlot::ARMOR_INVENTORY,
                    overrideInventory: true
                ),
                new ShopItem(
                    name: "Permanent Iron Armor",
                    value: [
                        ArmorInventory::SLOT_LEGS => VanillaItems::IRON_LEGGINGS(),
                        ArmorInventory::SLOT_FEET => VanillaItems::IRON_BOOTS()
                    ],
                    iconPath: "textures/items/iron_boots",
                    cost: new ItemCost(CostType::GOLD(), 12),
                    purchaseValidatorFn: function (ShopItem $item, Player $player, BWTeam $team): ?string {
                        assert(is_array($item->value));

                        $armorDifference = Utils::getArmorDifference($player->getArmorInventory()->getLeggings(), $item->value[ArmorInventory::SLOT_LEGS]);

                        if ($armorDifference >= 0) {
                            $player->broadcastSound(new FireExtinguishSound(), [$player]);
                            return TextFormat::GREEN . "You already have " . ($armorDifference === 0 ? 'this' : 'better') . " armor!";
                        }

                        return null;
                    },
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var Armor $item */
                        if (($level = $team->getUpgradeLevel(Upgrade::ARMOR())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
                        }
                        if ($item->getArmorSlot() === ArmorInventory::SLOT_FEET && ($level = $team->getUpgradeLevel(Upgrade::SOFT_BOOTS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), $level));
                        }
                        return $item;
                    },
                    onPurchase: function (ShopItem $item, Player $player, BWTeam $team): void {
                        $value = $item->getValue();
                        assert(is_array($value));
                        $team->setPermanent(
                            player: $player,
                            key: BWTeam::ARMOR,
                            value: $value
                        );
                    },
                    slot: ShopInventorySlot::ARMOR_INVENTORY,
                    overrideInventory: true
                ),
                new ShopItem(
                    name: "Permanent Diamond Armor",
                    value: [
                        ArmorInventory::SLOT_LEGS => VanillaItems::DIAMOND_LEGGINGS(),
                        ArmorInventory::SLOT_FEET => VanillaItems::DIAMOND_BOOTS()
                    ],
                    iconPath: "textures/items/diamond_boots",
                    cost: new ItemCost(CostType::EMERALD(), 6),
                    purchaseValidatorFn: function (ShopItem $item, Player $player, BWTeam $team): ?string {
                        assert(is_array($item->value));

                        $armorDifference = Utils::getArmorDifference($player->getArmorInventory()->getLeggings(), $item->value[ArmorInventory::SLOT_LEGS]);

                        if ($armorDifference >= 0) {
                            $player->broadcastSound(new FireExtinguishSound(), [$player]);
                            return TextFormat::GREEN . "You already have " . ($armorDifference === 0 ? 'this' : 'better') . " armor!";
                        }

                        return null;
                    },
                    itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                        /** @var Armor $item */
                        if (($level = $team->getUpgradeLevel(Upgrade::ARMOR())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
                        }
                        if ($item->getArmorSlot() === ArmorInventory::SLOT_FEET && ($level = $team->getUpgradeLevel(Upgrade::SOFT_BOOTS())) > 0) {
                            $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), $level));
                        }
                        return $item;
                    },
                    onPurchase: function (ShopItem $item, Player $player, BWTeam $team): void {
                        $value = $item->getValue();
                        assert(is_array($value));
                        $team->setPermanent(
                            player: $player,
                            key: BWTeam::ARMOR,
                            value: $value
                        );
                    },
                    slot: ShopInventorySlot::ARMOR_INVENTORY,
                    overrideInventory: true
                ),
            ]
        );
        self::register(
            name: "Tools",
            displayItem: VanillaItems::SHEARS(),
            iconPath: "textures/items/shears",
            items: [
                new UpgradeableShopItem(
                    name: BWTeam::SHEARS,
                    tiers: [
                        new ShopItem(
                            name: "Basic Shears",
                            value: VanillaItems::SHEARS()
                                ->setUnbreakable(),
                            iconPath: "textures/items/shears",
                            cost: new ItemCost(CostType::IRON(), 10),
                            description: "Basic Shears",
                        ),
                        new ShopItem(
                            name: "Advanced Shears",
                            value: VanillaItems::SHEARS()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1)),
                            iconPath: "textures/items/shears",
                            cost: new ItemCost(CostType::IRON(), 20),
                            description: "Advanced Shears (Efficiency I)",
                        ),
                    ],
                ),
                new UpgradeableShopItem(
                    name: BWTeam::PICKAXE,
                    tiers: [
                        new ShopItem(
                            name: "Wooden Pickaxe",
                            value: VanillaItems::WOODEN_PICKAXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1)),
                            iconPath: "textures/items/wood_pickaxe",
                            cost: new ItemCost(CostType::IRON(), 10),
                            description: "Wooden Pickaxe (Efficiency I)",
                        ),
                        new ShopItem(
                            name: "Iron Pickaxe",
                            value: VanillaItems::IRON_PICKAXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2)),
                            iconPath: "textures/items/iron_pickaxe",
                            cost: new ItemCost(CostType::IRON(), 10),
                            description: "Iron Pickaxe (Efficiency II)"
                        ),
                        new ShopItem(
                            name: "Gold Pickaxe",
                            value: VanillaItems::GOLDEN_PICKAXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3))
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2)),
                            iconPath: "textures/items/gold_pickaxe",
                            cost: new ItemCost(CostType::GOLD(), 3),
                            description: "Gold Pickaxe (Efficiency III, Sharpness II)"
                        ),
                        new ShopItem(
                            name: "Diamond Pickaxe",
                            value: VanillaItems::DIAMOND_PICKAXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3)),
                            iconPath: "textures/items/diamond_pickaxe",
                            cost: new ItemCost(CostType::GOLD(), 6),
                            description: "Diamond Pickaxe (Efficiency III)"
                        )
                    ],
                ),
                // TODO: Support Item Modifiers for upgradable shop items
                new UpgradeableShopItem(
                    name: BWTeam::AXE,
                    tiers: [
                        new ShopItem(
                            name: "Wooden Axe",
                            value: VanillaItems::WOODEN_AXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1)),
                            iconPath: "textures/items/wood_axe",
                            cost: new ItemCost(CostType::IRON(), 10),
                            description: "Wooden Axe (Efficiency I)",
                            itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                                if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                                }
                                return $item;
                            },
                        ),
                        new ShopItem(
                            name: "Stone Axe",
                            value: VanillaItems::STONE_AXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1)),
                            iconPath: "textures/items/stone_axe",
                            cost: new ItemCost(CostType::IRON(), 10),
                            description: "Stone Axe (Efficiency I)",
                            itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                                if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                                }
                                return $item;
                            },
                        ),
                        new ShopItem(
                            name: "Iron Axe",
                            value: VanillaItems::IRON_AXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2)),
                            iconPath: "textures/items/iron_axe",
                            cost: new ItemCost(CostType::GOLD(), 3),
                            description: "Iron Axe (Efficiency II)",
                            itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                                if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                                }
                                return $item;
                            },
                        ),
                        new ShopItem(
                            name: "Diamond Axe",
                            value: VanillaItems::DIAMOND_AXE()
                                ->setUnbreakable()
                                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3)),
                            iconPath: "textures/items/diamond_axe",
                            cost: new ItemCost(CostType::GOLD(), 6),
                            description: "Diamond Axe (Efficiency III)",
                            itemModifierFn: function (Item $item, Player $player, BWTeam $team): Item {
                                if (($level = $team->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                                }
                                return $item;
                            },
                        )
                    ],
                ),
            ]
        );
        self::register(
            name: "Potions",
            displayItem: VanillaBlocks::BREWING_STAND()->asItem(),
            iconPath: "textures/items/potion_bottle_drinkable",
            items: [
                new ShopItem(
                    name: "Speed Potion",
                    value: BWItems::SWIFTNESS_POTION(),
                    iconPath: "textures/items/potion_bottle_moveSpeed",
                    cost: new ItemCost(CostType::EMERALD(), 1),
                    description: "Speed around your enemies to make you harder to hit for 45 seconds!"
                ),
                new ShopItem(
                    name: "Jump Potion",
                    value: BWItems::STRONG_LEAPING_POTION(),
                    iconPath: "textures/items/potion_bottle_jump",
                    cost: new ItemCost(CostType::EMERALD(), 1),
                    description: "Jump higher for a quick escape for 45 seconds!"
                ),
                new ShopItem(
                    name: "Invisibility Potion",
                    value: BWItems::INVISIBILITY_POTION(),
                    iconPath: "textures/items/potion_bottle_moveSpeed",
                    cost: new ItemCost(CostType::EMERALD(), 2),
                    description: "Sneak past invisible to your opponents for 30 seconds!"
                ),
                new ShopItem(
                    name: "Strength Potion",
                    value: BWItems::STRENGTH_POTION(),
                    iconPath: "textures/items/potion_bottle_damageBoost",
                    cost: new ItemCost(CostType::EMERALD(), 2),
                    description: "Deal more damage to your oppenents in melee attacks for 30 seconds!"
                ),
                new ShopItem(
                    name: "Haste Potion",
                    value: BWItems::HASTE_POTION(),
                    iconPath: "textures/items/potion_bottle_digSpeed",
                    cost: new ItemCost(CostType::EMERALD(), 3),
                    description: "Break blocks in a flash with Haste III for 30 seconds!"
                ),
                new ShopItem(
                    name: "Levitation Potion",
                    value: BWItems::LEVITATION_POTION(),
                    iconPath: "textures/items/potion_bottle_levitation",
                    cost: new ItemCost(CostType::EMERALD(), 3),
                    description: "Take flight for " . BWItems::LEVITATION_DURATION . " seconds!"
                ),
            ]
        );
        self::register(
            name: "Defense",
            displayItem: VanillaBlocks::BED()->asItem(),
            iconPath: "textures/items/bed_red",
            items: [
                new ShopItem(
                    name: "Compact Pop-up Tower",
                    value: BWItems::POPUP_TOWER(),
                    iconPath: "textures/blocks/chest_front",
                    cost: new ItemCost(CostType::IRON(), 24),
                    description: "Place a pop-up defence!",
                ),
                new ShopItem(
                    name: "Bedbug",
                    value: BWItems::BEDBUG_SNOWBALL(),
                    iconPath: "textures/items/snowball",
                    cost: new ItemCost(CostType::IRON(), 30),
                    description: "Spawns silverfish where the snowball lands to distract your enemies."
                ),
                new ShopItem(
                    name: "Skeleton Army",
                    value: BWItems::SKELETON_ARMY_EGG(),
                    iconPath: "textures/items/spawn_egg",
                    cost: new ItemCost(CostType::IRON(), 40),
                    description: "Spawns a skeleton army to help defend your base."
                ),
                new ShopItem(
                    name: "Dream Defender",
                    value: BWItems::DEFENDER_EGG(),
                    iconPath: "textures/items/spawn_egg",
                    cost: new ItemCost(CostType::IRON(), 80),
                    description: "Iron Golem to help defend your base."
                ),
                new ShopItem(
                    name: "Water Bucket",
                    value: VanillaItems::WATER_BUCKET(),
                    iconPath: "textures/items/bucket_water",
                    cost: new ItemCost(CostType::GOLD(), 3, 6),
                    description: "Great to slow down approaching enemies. Can also protect against TNT and Landmines."
                ),
                new ShopItem(
                    name: "Sponge",
                    value: VanillaBlocks::SPONGE()->asItem()->setCount(4),
                    iconPath: "textures/blocks/sponge",
                    cost: new ItemCost(CostType::GOLD(), 3, 6),
                    description: "Great for soaking up water.",
                ),
                new ShopItem(
                    name: "Landmine",
                    value: CustomItemRegistry::LANDMINE(),
                    iconPath: "textures/items/bedwars/landmine",
                    cost: new ItemCost(CostType::GOLD(), 9, 13),
                    description: "Place a landmine to explode when stepped on!",
                ),
            ]
        );
        self::register(
            name: "Utility",
            displayItem: VanillaBlocks::TNT()->asItem(),
            iconPath: "textures/blocks/tnt_side",
            items: [
                new ShopItem(
                    name: "Fireball",
                    value: BWItems::FIREBALL(),
                    iconPath: "textures/items/fireball",
                    cost: new ItemCost(CostType::IRON(), 40),
                    description: "Great to knock back enemies walking on thin bridges or doing high Fireball jumps!"
                ),
                new ShopItem(
                    name: "Golden Apple",
                    value: VanillaItems::GOLDEN_APPLE(),
                    iconPath: "textures/items/apple_golden",
                    cost: new ItemCost(CostType::GOLD(), 3),
                    description: "Quickly heal after a fight."
                ),
                new ShopItem(
                    name: "Magic Milk",
                    value: BWItems::MAGIC_MILK(),
                    iconPath: "textures/items/bucket_milk",
                    cost: new ItemCost(CostType::GOLD(), 3, 4),
                    description: "Avoid triggering traps for 30 seconds after consuming."
                ),
                new ShopItem(
                    name: "TNT",
                    value: VanillaBlocks::TNT()->asItem(),
                    iconPath: "textures/blocks/tnt_side",
                    cost: new ItemCost(CostType::GOLD(), 4, 8),
                    description: "Instantly ignites! Great for destroying bed defences fast!"
                ),
                new ShopItem(
                    name: "Bridge Builder",
                    value: BWItems::BRIDGE_EGG(),
                    iconPath: "textures/items/spawn_egg",
                    cost: new ItemCost(CostType::GOLD(), 5),
                    description: "Spawns a turtle that creates a bridge in its trail. Maximum 32 blocks!"
                ),
                new ShopItem(
                    name: "Player Tracker",
                    value: Items::getCompass(),
                    iconPath: "textures/items/compass_item",
                    cost: new ItemCost(CostType::EMERALD(), 1),
                    description: "Find the location of your closes enemy player!",
                ),
                new ShopItem(
                    name: "Ender Pearl",
                    value: VanillaItems::ENDER_PEARL(),
                    iconPath: "textures/items/ender_pearl",
                    cost: new ItemCost(CostType::EMERALD(), 4),
                    description: "Teleport to different parts of the map. Useful for a quick getaway!"
                ),
            ]
        );
    }

    /**
     * @param ShopItem[] $items
     */
    private static function register(string $name, Item $displayItem, string $iconPath, array $items): void
    {
        self::_registryRegister($name, new self($name, $displayItem, $iconPath, $items));
    }

    /**
     * @return ShopCategory[]
     */
    public static function getAll(): array
    {
        /** @var ShopCategory[] $categories */
        $categories = self::_registryGetAll();
        return $categories;
    }

    /**
     * @param ShopItem[] $items
     */
    private function __construct(
        private readonly string $name,
        private readonly Item   $displayItem,
        private readonly string $iconPath,
        private readonly array  $items
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayItem(): Item
    {
        return clone $this->displayItem;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    /**
     * @return ShopItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function equals(ShopCategory $other): bool
    {
        return $this->name === $other->name;
    }

    public function resolveItemFromName(string $name): ShopItem
    {
        $found = array_filter($this->items, fn(ShopItem $shopItem) => $shopItem->getName() === $name);
        if (count($found) === 0) {
            throw new InvalidArgumentException("Unable to find item by name $name in category $this->name");
        }
        return $found[array_key_first($found)];
    }

    /**
     * Attempts to resolve a shop item from the given index, or null if not found.
     */
    public function resolveItemFromIndex(int $index): ?ShopItem
    {
        return $this->items[$index] ?? null;
    }

    public function resolveItemToIndex(string $name): int
    {
        foreach ($this->items as $index => $item) {
            if ($item->getName() === $name) {
                return $index;
            }
        }
        throw new InvalidArgumentException("Unable to resolve item $name to index");
    }

    public function resolveCategoryToIndex(): int
    {
        return match (true) {
            $this->equals(self::BLOCKS()) => 0,
            $this->equals(self::WEAPONS()) => 1,
            $this->equals(self::ARMOR()) => 2,
            $this->equals(self::TOOLS()) => 3,
            $this->equals(self::POTIONS()) => 4,
            $this->equals(self::DEFENSE()) => 5,
            $this->equals(self::UTILITY()) => 6,
            default => throw new InvalidArgumentException("Unable to resolve category $this->name to index")
        };
    }

    /**
     * Resolves a legacy category ID to its registry equivalent.
     */
    public static function resolveLegacyIdToCategory(int $id): ?ShopCategory
    {
        return match ($id) {
            0 => self::BLOCKS(),
            1 => self::WEAPONS(),
            2 => self::ARMOR(),
            3 => self::TOOLS(),
            4 => self::POTIONS(),
            5 => self::DEFENSE(),
            6 => self::UTILITY(),
            default => null
        };
    }
}