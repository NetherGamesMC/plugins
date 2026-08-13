<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars;

use libminigames\loot\LootEntry;
use libminigames\loot\LootPool;
use libminigames\loot\LootTable;
use libminigames\loot\metadata\count\RandomCountMetadata;
use libminigames\loot\metadata\count\WeightedCountMetadata;
use libminigames\loot\metadata\enchantment\ChancedEnchantmentMetadata;
use libminigames\loot\metadata\enchantment\WeightedEnchantmentMetadata;
use libminigames\loot\rolls\RandomRoll;
use libminigames\loot\rolls\WeightedRoll;
use libminigames\pool\WeightedEntry;
use libVanilla\LibVanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\utils\RegistryTrait;
use skywars\items\SWItems;
use skywars\utils\Items;

/**
 * @method static self NORMAL()
 * @method static self INSANE()
 */
final class GameTableType
{
    use RegistryTrait;

    public function __construct(protected LootTable $spawnTable, protected LootTable $middleTable)
    {
    }

    protected static function setup(): void
    {
        self::register(
            name: "normal",
            spawnTable: new LootTable(
                pools: [
                    // Armor
                    new LootPool(
                        entries: [
                            // Helmets
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_HELMET(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_HELMET(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_HELMET(), weight: 5),
                                ],
                                weight: 25
                            ),
                            // Chestplates
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_CHESTPLATE(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_CHESTPLATE(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_CHESTPLATE(), weight: 5),
                                ],
                                weight: 25
                            ),
                            // Leggings
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_LEGGINGS(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_LEGGINGS(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_LEGGINGS(), weight: 5),
                                ],
                                weight: 25
                            ),
                            // Boots
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_BOOTS(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_BOOTS(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_BOOTS(), weight: 5),
                                ],
                                weight: 25
                            ),
                        ],
                        // Roll one item 70% of the time & two items 30% of the time
                        roll: new WeightedRoll([
                            new WeightedEntry(1, 70),
                            new WeightedEntry(2, 30)
                        ])
                    ),
                    // Main Tools
                    new LootPool(
                        entries: [
                            // Sword
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_SWORD(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_SWORD(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_SWORD(), weight: 5),
                                ],
                                weight: 65,
                            ),
                            new LootEntry(value: [VanillaItems::BOW(), VanillaItems::ARROW()->setCount(16)], weight: 20),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                        ]
                    ),
                    // Extra Tools
                    new LootPool(
                        entries: [
                            // Axe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_AXE(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_AXE(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_AXE(), weight: 5),
                                ],
                                weight: 25,
                            ),
                            // Pickaxe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_PICKAXE(), weight: 75),
                                    new LootEntry(value: VanillaItems::IRON_PICKAXE(), weight: 20),
                                    new LootEntry(value: VanillaItems::DIAMOND_PICKAXE(), weight: 5),
                                ],
                                weight: 25,
                            ),
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 10),
                            new LootEntry(value: LibVanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SWItems::GRAPPLING_ROD(), weight: 25),
                        ],
                        roll: new RandomRoll(0, 1)
                    ),
                    // Blocks
                    new LootPool(entries: [
                        new LootEntry(value: VanillaBlocks::STONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::COBBLESTONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::DIRT()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::OAK_PLANKS()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                    ]),
                    // Food
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaItems::GOLDEN_APPLE(), weight: 20, countMetadata: new WeightedCountMetadata(entries: [
                                // Roll one item 50% of the time
                                new WeightedEntry(1, 50),
                                // Roll two items 30% of the time
                                new WeightedEntry(2, 30),
                                // Roll three items 20% of the time
                                new WeightedEntry(3, 20)
                            ])),
                            new LootEntry(value: VanillaItems::STEAK(), weight: 40, countMetadata: new RandomCountMetadata(3, 10)),
                            new LootEntry(value: VanillaItems::COOKED_PORKCHOP(), weight: 40, countMetadata: new RandomCountMetadata(3, 10)),
                        ],
                        // Roll food 65% of the time
                        roll: new WeightedRoll(entries: [new WeightedEntry(0, 35), new WeightedEntry(1, 65)])
                    ),
                    // Splash Potions
                    new LootPool(
                        entries: [
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::REGENERATION), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SWIFTNESS), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_SWIFTNESS), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRENGTH), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_STRENGTH), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::FIRE_RESISTANCE), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::POISON), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::WEAKNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SLOWNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HARMING), weight: 12.5),
                        ]
                    ),
                    // Utility
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaBlocks::TNT()->asItem(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: VanillaItems::WATER_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::EGG(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::SNOWBALL(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 12.5),
                            new LootEntry(
                            // TODO: Per-item metadata (likely through nested entries)
                                value: [VanillaItems::EXPERIENCE_BOTTLE()->setCount(7), VanillaItems::LAPIS_LAZULI()->setCount(3)],
                                weight: 12.5
                            ),
                        ],
                        roll: new RandomRoll(0, 1)
                    ),
                    // Enchanted Books
                    new LootPool(
                        entries: [
                            // Sharpness Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::SHARPNESS(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 50),
                                            new WeightedEntry(3, 25)
                                        ]
                                    )
                                ]
                            ),
                            // Protection Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::PROTECTION(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 50),
                                            new WeightedEntry(3, 25)
                                        ]
                                    )
                                ]
                            ),
                            // Power Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)),
                                weight: 20,
                            ),
                            // Fire Aspect Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1)),
                                weight: 20,
                            ),
                            // Flame Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1)),
                                weight: 20,
                            ),
                        ],
                        // Roll an enchanted book 30% of the time
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 70),
                            new WeightedEntry(1, 30)
                        ])
                    )
                ]
            ),
            middleTable: new LootTable(
                pools: [
                    // Armor
                    new LootPool(
                        entries: [
                            // Helmets
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_HELMET(), weight: 20, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::IRON_HELMET(), weight: 50, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::DIAMOND_HELMET(), weight: 30, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                ],
                                weight: 25
                            ),
                            // Chestplates
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_CHESTPLATE(), weight: 20, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::IRON_CHESTPLATE(), weight: 50, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::DIAMOND_CHESTPLATE(), weight: 30, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                ],
                                weight: 25
                            ),
                            // Leggings
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_LEGGINGS(), weight: 20, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::IRON_LEGGINGS(), weight: 50, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::DIAMOND_LEGGINGS(), weight: 30, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                ],
                                weight: 25
                            ),
                            // Boots
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::CHAINMAIL_BOOTS(), weight: 20, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::IRON_BOOTS(), weight: 50, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                    new LootEntry(value: VanillaItems::DIAMOND_BOOTS(), weight: 30, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::PROTECTION(), 1, 20)]),
                                ],
                                weight: 25
                            ),
                        ],
                        // Roll one item 40% of the time & two items 60% of the time
                        roll: new WeightedRoll([
                            new WeightedEntry(1, 40),
                            new WeightedEntry(2, 60)
                        ])
                    ),
                    // Main Tools
                    new LootPool(
                        entries: [
                            // Sword
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::STONE_SWORD(),
                                        weight: 20,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 10),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10)
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_SWORD(),
                                        weight: 50,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 10),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10)
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_SWORD(),
                                        weight: 30,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 10),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10)
                                        ]
                                    ),
                                ],
                                weight: 65,
                            ),
                            new LootEntry(value: [VanillaItems::BOW(), VanillaItems::ARROW()->setCount(16)], weight: 20),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                        ]
                    ),
                    // Extra Tools
                    new LootPool(
                        entries: [
                            // Axe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_AXE(), weight: 20, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 25)]),
                                    new LootEntry(value: VanillaItems::IRON_AXE(), weight: 50, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 25)]),
                                    new LootEntry(value: VanillaItems::DIAMOND_AXE(), weight: 30, metadata: [new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 25)]),
                                ],
                                weight: 25,
                            ),
                            // Pickaxe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_PICKAXE(), weight: 20),
                                    new LootEntry(value: VanillaItems::IRON_PICKAXE(), weight: 50),
                                    new LootEntry(value: VanillaItems::DIAMOND_PICKAXE(), weight: 30),
                                ],
                                weight: 25,
                            ),
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 10),
                            new LootEntry(value: LibVanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SWItems::GRAPPLING_ROD(), weight: 25),
                        ],
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 35),
                            new WeightedEntry(1, 65)
                        ])
                    ),
                    // Blocks
                    new LootPool(entries: [
                        new LootEntry(value: VanillaBlocks::STONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::COBBLESTONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::DIRT()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::OAK_PLANKS()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                    ]),
                    // Food
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaItems::GOLDEN_APPLE(), weight: 30, countMetadata: new WeightedCountMetadata(entries: [
                                // Roll three items 70% of the time
                                new WeightedEntry(3, 70),
                                // Roll four items 30% of the time
                                new WeightedEntry(4, 30),
                            ])),
                            new LootEntry(value: VanillaItems::STEAK(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                            new LootEntry(value: VanillaItems::COOKED_PORKCHOP(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                        ],
                        // Roll food 65% of the time
                        roll: new WeightedRoll(entries: [new WeightedEntry(0, 35), new WeightedEntry(1, 65)])
                    ),
                    // Splash Potions
                    new LootPool(
                        entries: [
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::REGENERATION), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SWIFTNESS), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_SWIFTNESS), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRENGTH), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_STRENGTH), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::FIRE_RESISTANCE), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::POISON), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::WEAKNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SLOWNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HARMING), weight: 12.5),
                        ]
                    ),
                    // Utility
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaBlocks::TNT()->asItem(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(2, 60),
                                new WeightedEntry(3, 30),
                                new WeightedEntry(4, 10)
                            ])),
                            new LootEntry(value: VanillaItems::WATER_BUCKET(), weight: 7.5),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 7.5),
                            new LootEntry(value: SWItems::SKELETON_SPAWN_EGG(), weight: 5),
                            new LootEntry(value: VanillaItems::ZOMBIE_SPAWN_EGG(), weight: 5),
                            new LootEntry(value: VanillaItems::EGG(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::SNOWBALL(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 12.5),
                            new LootEntry(
                            // TODO: Per-item metadata (likely through nested entries)
                                value: [VanillaItems::EXPERIENCE_BOTTLE()->setCount(7), VanillaItems::LAPIS_LAZULI()->setCount(3)],
                                weight: 12.5
                            ),
                        ],
                        // Pull one item 65% of the time
                        roll: new WeightedRoll([new WeightedEntry(0, 35), new WeightedEntry(1, 65)])
                    ),
                    // Enchanted Books
                    new LootPool(
                        entries: [
                            // Sharpness Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::SHARPNESS(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 25),
                                            new WeightedEntry(3, 50)
                                        ]
                                    )
                                ]
                            ),
                            // Protection Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::PROTECTION(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 25),
                                            new WeightedEntry(3, 50)
                                        ]
                                    )
                                ]
                            ),
                            // Power Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)),
                                weight: 20,
                            ),
                            // Fire Aspect Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1)),
                                weight: 20,
                            ),
                            // Flame Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1)),
                                weight: 20,
                            ),
                        ],
                        // Roll an enchanted book 50% of the time
                        roll: new RandomRoll(0, 1)
                    )
                ]
            )
        );

        self::register(
            name: "insane",
            spawnTable: new LootTable(
                pools: [
                    // Armor
                    new LootPool(
                        entries: [
                            // Helmets
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_HELMET(),
                                        weight: 30,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_HELMET(),
                                        weight: 50,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_HELMET(),
                                        weight: 20,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Chestplates
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_CHESTPLATE(),
                                        weight: 30,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_CHESTPLATE(),
                                        weight: 50,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_CHESTPLATE(),
                                        weight: 20,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Leggings
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_LEGGINGS(),
                                        weight: 30,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_LEGGINGS(),
                                        weight: 50,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_LEGGINGS(),
                                        weight: 20,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Boots
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_BOOTS(),
                                        weight: 30,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_BOOTS(),
                                        weight: 50,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_BOOTS(),
                                        weight: 20,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                        ],
                        // Roll one item 70% of the time & two items 30% of the time
                        roll: new WeightedRoll([
                            new WeightedEntry(1, 70),
                            new WeightedEntry(2, 30)
                        ])
                    ),
                    // Main Tools
                    new LootPool(
                        entries: [
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::STONE_SWORD(),
                                        weight: 30,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 2, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10),
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_SWORD(),
                                        weight: 50,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 2, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10),
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_SWORD(),
                                        weight: 20,
                                        metadata: [
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 1, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::SHARPNESS(), 2, 30),
                                            new ChancedEnchantmentMetadata(VanillaEnchantments::FIRE_ASPECT(), 1, 10),
                                        ]
                                    ),
                                ],
                                weight: 65
                            ),
                            // TODO: Random enchantment levels for each item in a loot entry array
                            new LootEntry(value: [VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)), VanillaItems::ARROW()->setCount(16)], weight: 15),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                            new LootEntry(value: LibVanillaItems::TRIDENT(), weight: 5),
                        ]
                    ),
                    // Extra Tools
                    new LootPool(
                        entries: [
                            // Axe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::STONE_AXE(),
                                        weight: 30,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_AXE(),
                                        weight: 50,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_AXE(),
                                        weight: 20,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 60),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25,
                            ),
                            // Pickaxe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_PICKAXE(), weight: 30),
                                    new LootEntry(value: VanillaItems::IRON_PICKAXE(), weight: 50),
                                    new LootEntry(value: VanillaItems::DIAMOND_PICKAXE(), weight: 20),
                                ],
                                weight: 25,
                            ),
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 10),
                            new LootEntry(value: LibVanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SWItems::GRAPPLING_ROD(), weight: 25),
                        ],
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 35),
                            new WeightedEntry(1, 65)
                        ])
                    ),
                    // Blocks
                    new LootPool(entries: [
                        new LootEntry(value: VanillaBlocks::STONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::COBBLESTONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::DIRT()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::OAK_PLANKS()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                    ]),
                    // Food
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaItems::GOLDEN_APPLE(), weight: 30, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(2, 30),
                                // Roll three items 70% of the time
                                new WeightedEntry(3, 40),
                                // Roll four items 30% of the time
                                new WeightedEntry(4, 30),
                            ])),
                            new LootEntry(value: VanillaItems::STEAK(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                            new LootEntry(value: VanillaItems::COOKED_PORKCHOP(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                        ],
                        // Roll food 65% of the time
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 35),
                            new WeightedEntry(1, 65)
                        ])
                    ),
                    // Splash Potions
                    new LootPool(
                        entries: [
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::REGENERATION), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SWIFTNESS), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_SWIFTNESS), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRENGTH), weight: 75),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_STRENGTH), weight: 25),
                                ],
                                weight: 12.5
                            ),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::FIRE_RESISTANCE), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::POISON), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::WEAKNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SLOWNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HARMING), weight: 12.5),
                        ]
                    ),
                    // Utility
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaBlocks::TNT()->asItem(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(2, 60),
                                new WeightedEntry(3, 30),
                                new WeightedEntry(4, 10)
                            ])),
                            new LootEntry(value: VanillaItems::WATER_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::EGG(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::SNOWBALL(), weight: 12.5, countMetadata: new RandomCountMetadata(8, 12)),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 12.5),
                            new LootEntry(
                            // TODO: Per-item metadata (likely through nested entries)
                                value: [VanillaItems::EXPERIENCE_BOTTLE()->setCount(7), VanillaItems::LAPIS_LAZULI()->setCount(3)],
                                weight: 12.5
                            ),
                        ],
                        roll: new RandomRoll(0, 1)
                    ),
                    // Enchanted Books
                    new LootPool(
                        entries: [
                            // Sharpness Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::SHARPNESS(),
                                        entries: [
                                            new WeightedEntry(1, 30),
                                            new WeightedEntry(2, 40),
                                            new WeightedEntry(3, 30)
                                        ]
                                    )
                                ]
                            ),
                            // Protection Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::PROTECTION(),
                                        entries: [
                                            new WeightedEntry(1, 30),
                                            new WeightedEntry(2, 40),
                                            new WeightedEntry(3, 30)
                                        ]
                                    )
                                ]
                            ),
                            // Power Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)),
                                weight: 20,
                            ),
                            // Fire Aspect Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1)),
                                weight: 20,
                            ),
                            // Flame Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1)),
                                weight: 20,
                            ),
                        ],
                        // Roll an enchanted book 50% of the time
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 70),
                            new WeightedEntry(1, 30)
                        ])
                    )
                ]
            ),
            middleTable: new LootTable(
                pools: [
                    // Armor
                    // TODO: Random enchantments
                    new LootPool(
                        entries: [
                            // Helmets
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_HELMET(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_HELMET(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_HELMET(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Chestplates
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_CHESTPLATE(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_CHESTPLATE(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_CHESTPLATE(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Leggings
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_LEGGINGS(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_LEGGINGS(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_LEGGINGS(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                            // Boots
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::CHAINMAIL_BOOTS(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_BOOTS(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_BOOTS(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::PROTECTION(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            )
                                        ]
                                    ),
                                ],
                                weight: 25
                            ),
                        ],
                        // Roll one item 30% of the time & two items 70% of the time
                        roll: new WeightedRoll([
                            new WeightedEntry(1, 30),
                            new WeightedEntry(2, 70)
                        ])
                    ),
                    // Main Tools
                    new LootPool(
                        entries: [
                            // TODO: Random enchantments
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::STONE_SWORD(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                            new ChancedEnchantmentMetadata(enchantment: VanillaEnchantments::FIRE_ASPECT(), level: 1, chance: 10)
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_SWORD(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                            new ChancedEnchantmentMetadata(enchantment: VanillaEnchantments::FIRE_ASPECT(), level: 1, chance: 10)
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_SWORD(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                            new ChancedEnchantmentMetadata(enchantment: VanillaEnchantments::FIRE_ASPECT(), level: 1, chance: 10)
                                        ]
                                    ),
                                ],
                                weight: 62.5
                            ),
                            new LootEntry(
                            // TODO: Random enchantments per item
                                value: [
                                    VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 2)),
                                    VanillaItems::ARROW()->setCount(16)
                                ],
                                weight: 12.5
                            ),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 12.5),
                            new LootEntry(value: LibVanillaItems::TRIDENT(), weight: 12.5),
                        ]
                    ),
                    // Extra Tools
                    new LootPool(
                        entries: [
                            // Axe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(
                                        value: VanillaItems::STONE_AXE(),
                                        weight: 25,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::IRON_AXE(),
                                        weight: 35,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                        ]
                                    ),
                                    new LootEntry(
                                        value: VanillaItems::DIAMOND_AXE(),
                                        weight: 40,
                                        metadata: [
                                            new WeightedEnchantmentMetadata(
                                                enchantment: VanillaEnchantments::SHARPNESS(),
                                                entries: [
                                                    new WeightedEntry(0, 40),
                                                    new WeightedEntry(1, 20),
                                                    new WeightedEntry(2, 20),
                                                    new WeightedEntry(3, 20)
                                                ]
                                            ),
                                        ]
                                    ),
                                ],
                                weight: 25,
                            ),
                            // Pickaxe
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::STONE_PICKAXE(), weight: 25),
                                    new LootEntry(value: VanillaItems::IRON_PICKAXE(), weight: 35),
                                    new LootEntry(value: VanillaItems::DIAMOND_PICKAXE(), weight: 40),
                                ],
                                weight: 25,
                            ),
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 10),
                            new LootEntry(value: LibVanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SWItems::GRAPPLING_ROD(), weight: 25),
                        ],
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 35),
                            new WeightedEntry(1, 65)
                        ])
                    ),
                    // Blocks
                    new LootPool(entries: [
                        new LootEntry(value: VanillaBlocks::STONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::COBBLESTONE()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::DIRT()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                        new LootEntry(value: VanillaBlocks::OAK_PLANKS()->asItem(), weight: 25, countMetadata: new RandomCountMetadata(24, 64)),
                    ]),
                    // Food
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaItems::GOLDEN_APPLE(), weight: 30, countMetadata: new WeightedCountMetadata(entries: [
                                // Roll three items 70% of the time
                                new WeightedEntry(3, 70),
                                // Roll four items 30% of the time
                                new WeightedEntry(4, 30),
                            ])),
                            new LootEntry(value: VanillaItems::STEAK(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                            new LootEntry(value: VanillaItems::COOKED_PORKCHOP(), weight: 35, countMetadata: new RandomCountMetadata(3, 10)),
                        ],
                        // Roll food 65% of the time
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 20),
                            new WeightedEntry(1, 80)
                        ])
                    ),
                    // Splash Potions
                    new LootPool(
                        entries: [
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::REGENERATION), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_REGENERATION), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SWIFTNESS), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_SWIFTNESS), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            LootPool::createEntry(
                                entries: [
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRENGTH), weight: 25),
                                    new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_STRENGTH), weight: 75),
                                ],
                                weight: 12.5
                            ),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::FIRE_RESISTANCE), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::POISON), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::WEAKNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::SLOWNESS), weight: 12.5),
                            new LootEntry(value: VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HARMING), weight: 12.5),
                        ],
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 20),
                            new WeightedEntry(1, 80)
                        ])
                    ),
                    // Utility
                    new LootPool(
                        entries: [
                            new LootEntry(value: VanillaBlocks::TNT()->asItem(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 40),
                                new WeightedEntry(4, 30)
                            ])),
                            new LootEntry(value: VanillaItems::WATER_BUCKET(), weight: 7.5),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 7.5),
                            new LootEntry(value: SWItems::SKELETON_SPAWN_EGG(), weight: 5),
                            new LootEntry(value: VanillaItems::ZOMBIE_SPAWN_EGG(), weight: 5),
                            new LootEntry(value: VanillaItems::EGG(), weight: 12.5, countMetadata: new RandomCountMetadata(12, 16)),
                            new LootEntry(value: VanillaItems::SNOWBALL(), weight: 12.5, countMetadata: new RandomCountMetadata(12, 16)),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 12.5),
                            new LootEntry(
                            // TODO: Per-item metadata (likely through nested entries)
                                value: [VanillaItems::EXPERIENCE_BOTTLE()->setCount(7), VanillaItems::LAPIS_LAZULI()->setCount(3)],
                                weight: 12.5
                            ),
                        ],
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 20),
                            new WeightedEntry(1, 80)
                        ])
                    ),
                    // Enchanted Books
                    new LootPool(
                        entries: [
                            // Sharpness Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::SHARPNESS(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 25),
                                            new WeightedEntry(3, 50)
                                        ]
                                    )
                                ]
                            ),
                            // Protection Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK(),
                                weight: 20,
                                metadata: [
                                    new WeightedEnchantmentMetadata(
                                        enchantment: VanillaEnchantments::PROTECTION(),
                                        entries: [
                                            new WeightedEntry(1, 25),
                                            new WeightedEntry(2, 25),
                                            new WeightedEntry(3, 50)
                                        ]
                                    )
                                ]
                            ),
                            // Power Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1)),
                                weight: 20,
                            ),
                            // Fire Aspect Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 1)),
                                weight: 20,
                            ),
                            // Flame Book
                            new LootEntry(
                                value: VanillaItems::ENCHANTED_BOOK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1)),
                                weight: 20,
                            ),
                        ],
                        // Roll an enchanted book 50% of the time
                        roll: new WeightedRoll(entries: [
                            new WeightedEntry(0, 50),
                            new WeightedEntry(1, 50)
                        ])
                    )
                ]
            )
        );

    }

    protected static function register(string $name, LootTable $spawnTable, LootTable $middleTable): void
    {
        self::_registryRegister($name, new self($spawnTable, $middleTable));
    }

    public function getSpawnTable(): LootTable
    {
        return $this->spawnTable;
    }

    public function getMiddleTable(): LootTable
    {
        return $this->middleTable;
    }
}