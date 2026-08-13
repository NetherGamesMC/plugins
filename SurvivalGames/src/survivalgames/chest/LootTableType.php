<?php
declare(strict_types=1);

namespace survivalgames\chest;

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
use survivalgames\items\SGItems;
use survivalgames\utils\Items;

/**
 * @method static LootTable REGULAR()
 * @method static LootTable MIDDLE()
 */
final class LootTableType
{
    use RegistryTrait;

    protected static function setup(): void
    {
        self::register(
            name: "regular",
            table: new LootTable(
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
                                weight: 50,
                            ),
                            new LootEntry(value: [VanillaItems::BOW(), VanillaItems::ARROW()->setCount(16)], weight: 20),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                            new LootEntry(value: VanillaItems::STICK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1)), weight: 15),
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
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 25),
                            new LootEntry(value: VanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SGItems::GRAPPLING_ROD(), weight: 25),
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
                            new LootEntry(value: VanillaBlocks::COBWEB()->asItem(), weight: 12.5, countMetadata: new RandomCountMetadata(3, 5)),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 50),
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
            )
        );
        self::register(
            name: "middle",
            table: new LootTable(
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
                                weight: 50,
                            ),
                            new LootEntry(value: [VanillaItems::BOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                            new LootEntry(value: [LibVanillaItems::CROSSBOW(), VanillaItems::ARROW()->setCount(16)], weight: 15),
                            new LootEntry(value: LibVanillaItems::TRIDENT(), weight: 5),
                            new LootEntry(value: VanillaItems::STICK()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1)), weight: 15),
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
                            //new LootEntry(value: LibVanillaItems::SHIELD()->setDamage(300), weight: 25),
                            new LootEntry(value: LibVanillaItems::FISHING_ROD(), weight: 25),
                            new LootEntry(value: SGItems::GRAPPLING_ROD(), weight: 25),
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
                            new LootEntry(value: VanillaBlocks::COBWEB()->asItem(), weight: 12.5, countMetadata: new RandomCountMetadata(3, 5)),
                            new LootEntry(value: VanillaItems::LAVA_BUCKET(), weight: 12.5),
                            new LootEntry(value: VanillaItems::ENDER_PEARL(), weight: 12.5, countMetadata: new WeightedCountMetadata(entries: [
                                new WeightedEntry(1, 60),
                                new WeightedEntry(2, 30),
                                new WeightedEntry(3, 10)
                            ])),
                            new LootEntry(value: Items::getCompass(), weight: 50),
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
    }

    protected static function register(string $name, LootTable $table): void
    {
        self::_registryRegister($name, $table);
    }
}
