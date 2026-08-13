<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item;

use Closure;
use InvalidArgumentException;
use NetherGames\NGEssentials\item\component\MaxStackSizeComponent;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use function array_keys;
use function array_map;
use function array_values;

/**
 * @method static SimpleCustomItem CHANGELOG()
 * @method static SimpleCustomItem COSMETICS()
 * @method static SimpleCustomItem ENCHANTED_BOOK_COMMON()
 * @method static SimpleCustomItem ENCHANTED_BOOK_MYTHICAL()
 * @method static SimpleCustomItem ENCHANTED_BOOK_RARE()
 * @method static SimpleCustomItem ENCHANTED_BOOK_UNCOMMON()
 * @method static SimpleCustomItem HELPER_HARVESTER()
 * @method static SimpleCustomItem HELPER_LUMBERJACK()
 * @method static SimpleCustomItem HELPER_MINER()
 * @method static SimpleCustomItem KITS()
 * @method static SimpleCustomItem KIT()
 * @method static SimpleCustomItem LAST_CHECKPOINT()
 * @method static SimpleCustomItem LAYOUT_EDITOR()
 * @method static SimpleCustomItem LEAVE()
 * @method static SimpleCustomItem LUCKY_SHARD()
 * @method static SimpleCustomItem MONEY_POUCH()
 * @method static SimpleCustomItem NO_CLIP()
 * @method static SimpleCustomItem PAUSE()
 * @method static SimpleCustomItem PLAY()
 * @method static SimpleCustomItem PLAY_AGAIN()
 * @method static SimpleCustomItem POWER_SHARD()
 * @method static SimpleCustomItem PREFERENCES()
 * @method static SimpleCustomItem RESTART()
 * @method static SimpleCustomItem SCENARIO()
 * @method static SimpleCustomItem SOCIAL_MENU()
 * @method static SimpleCustomItem SPEED_MODIFIER()
 * @method static SimpleCustomItem STAFF_PORTAL()
 * @method static SimpleCustomItem TELEPORTER()
 * @method static SimpleCustomItem TRADE_BUTTON_ACCEPT()
 * @method static SimpleCustomItem TRADE_BUTTON_DENY()
 * @method static SimpleCustomItem TRADE_ICON_INFORMATION()
 * @method static SimpleCustomItem TRADE_SEPARATOR()
 * @method static SimpleCustomItem ZONES()
 * @method static SimpleCustomItem LANDMINE()
 *
 * @method static SimpleCustomItem TEAM_SELECTOR_WHITE()
 * @method static SimpleCustomItem TEAM_SELECTOR_ORANGE()
 * @method static SimpleCustomItem TEAM_SELECTOR_MAGENTA()
 * @method static SimpleCustomItem TEAM_SELECTOR_LIGHT_BLUE()
 * @method static SimpleCustomItem TEAM_SELECTOR_YELLOW()
 * @method static SimpleCustomItem TEAM_SELECTOR_LIME()
 * @method static SimpleCustomItem TEAM_SELECTOR_PINK()
 * @method static SimpleCustomItem TEAM_SELECTOR_GRAY()
 * @method static SimpleCustomItem TEAM_SELECTOR_LIGHT_GRAY()
 * @method static SimpleCustomItem TEAM_SELECTOR_CYAN()
 * @method static SimpleCustomItem TEAM_SELECTOR_PURPLE()
 * @method static SimpleCustomItem TEAM_SELECTOR_BLUE()
 * @method static SimpleCustomItem TEAM_SELECTOR_BROWN()
 * @method static SimpleCustomItem TEAM_SELECTOR_GREEN()
 * @method static SimpleCustomItem TEAM_SELECTOR_RED()
 * @method static SimpleCustomItem TEAM_SELECTOR_BLACK()
 *
 * @method static Item IRON_GOLEM_SPAWN_EGG()
 * @method static Item SKELETON_SPAWN_EGG()
 * @method static Item TURTLE_SPAWN_EGG()
 */
final class CustomItemRegistry
{
    use CloningRegistryTrait;

    private static bool $registered = false;

    /** @var array<string, int> */
    private static array $registeredItems = [];
    /** @var array<string, ItemTypeEntry> */
    private static array $itemTableEntries = [];
    /** @var array<string, ItemComponents> */
    private static array $itemComponentEntries = [];
    private static ?Experiments $experiment = null;

    public static function forceInitialization(): void
    {
        self::checkInit();
    }

    protected static function setup(): void
    {
        self::registerSimpleCustomItem('Changelog', CustomItemTypeNames::CHANGELOG);
        self::registerSimpleCustomItem('Cosmetics', CustomItemTypeNames::COSMETICS);
        self::registerSimpleCustomItem('Kits', CustomItemTypeNames::KITS);
        self::registerSimpleCustomItem('LayoutEditor', CustomItemTypeNames::LAYOUT_EDITOR);
        self::registerSimpleCustomItem('Leave', CustomItemTypeNames::LEAVE);
        self::registerSimpleCustomItem('NoClip', CustomItemTypeNames::NO_CLIP);
        self::registerSimpleCustomItem('Pause', CustomItemTypeNames::PAUSE);
        self::registerSimpleCustomItem('Play', CustomItemTypeNames::PLAY);
        self::registerSimpleCustomItem('PlayAgain', CustomItemTypeNames::PLAY_AGAIN);
        self::registerSimpleCustomItem('Preferences', CustomItemTypeNames::PREFERENCES);
        self::registerSimpleCustomItem('Scenario', CustomItemTypeNames::SCENARIO);
        self::registerSimpleCustomItem('SocialMenu', CustomItemTypeNames::SOCIAL_MENU);
        self::registerSimpleCustomItem('SpeedModifier', CustomItemTypeNames::SPEED_MODIFIER);
        self::registerSimpleCustomItem('StaffPortal', CustomItemTypeNames::STAFF_PORTAL);
        self::registerSimpleCustomItem('Teleporter', CustomItemTypeNames::TELEPORTER);
        self::registerSimpleCustomItem('Zones', CustomItemTypeNames::ZONES);
        self::registerSimpleCustomItem('Restart', CustomItemTypeNames::RESTART);
        self::registerSimpleCustomItem('LastCheckpoint', CustomItemTypeNames::LAST_CHECKPOINT);

        self::registerSimpleCustomItem('LuckyShard', CustomItemTypeNames::MMO_SHARD_LUCKY, null, ["nethergames:lucky_shard"]);   // has to be kept till the new skyblock & factions season
        self::registerSimpleCustomItem('PowerShard', CustomItemTypeNames::MMO_SHARD_POWER, null, ["nethergames:power_shard"]);
        self::registerSimpleCustomItem('Kit', CustomItemTypeNames::MMO_KIT, null, ["nethergames:kit_item"], components: [new MaxStackSizeComponent(1)]);
        self::registerSimpleCustomItem('EnchantedBookCommon', CustomItemTypeNames::MMO_ENCHANTED_BOOK_COMMON, null, ["nethergames:common_enchanted_book"], components: [new MaxStackSizeComponent(1)]);
        self::registerSimpleCustomItem('EnchantedBookUncommon', CustomItemTypeNames::MMO_ENCHANTED_BOOK_UNCOMMON, null, ["nethergames:uncommon_enchanted_book"], components: [new MaxStackSizeComponent(1)]);
        self::registerSimpleCustomItem('EnchantedBookRare', CustomItemTypeNames::MMO_ENCHANTED_BOOK_RARE, null, ["nethergames:rare_enchanted_book"], components: [new MaxStackSizeComponent(1)]);
        self::registerSimpleCustomItem('EnchantedBookMythical', CustomItemTypeNames::MMO_ENCHANTED_BOOK_MYTHICAL, null, ["nethergames:mythical_enchanted_book"], components: [new MaxStackSizeComponent(1)]);
        self::registerSimpleCustomItem('MoneyPouch', CustomItemTypeNames::MMO_MONEY_POUCH, null, ["nethergames:money_pouch"]);

        self::registerSimpleCustomItem('TradeSeparator', CustomItemTypeNames::MMO_TRADE_SEPARATOR);
        self::registerSimpleCustomItem('TradeButtonAccept', CustomItemTypeNames::MMO_TRADE_BUTTON_ACCEPT);
        self::registerSimpleCustomItem('TradeButtonDeny', CustomItemTypeNames::MMO_TRADE_BUTTON_DENY);
        self::registerSimpleCustomItem('TradeIconInformation', CustomItemTypeNames::MMO_TRADE_ICON_INFORMATION);

        self::registerSimpleCustomItem("HelperMiner", CustomItemTypeNames::SKYBLOCK_HELPER_MINER, null, ["nethergames:miner_helper"]);    // has to be kept till the new skyblock season
        self::registerSimpleCustomItem("HelperHarvester", CustomItemTypeNames::SKYBLOCK_HELPER_HARVESTER, null, ["nethergames:harvester_helper"]);
        self::registerSimpleCustomItem("HelperLumberjack", CustomItemTypeNames::SKYBLOCK_HELPER_LUMBERJACK, null, ["nethergames:lumberjack_helper"]);

        self::registerSimpleCustomItem('ThrowableTnt', CustomItemTypeNames::FACTIONS_TNT_THROWABLE, null, ["nethergames:tnt_throwable"]); // has to be kept till the new factions season
        self::registerSimpleCustomItem('ThrowableTntUnderwater', CustomItemTypeNames::FACTIONS_TNT_THROWABLE_UNDERWATER, null, ["nethergames:tnt_throwable_underwater"]);
        self::registerSimpleCustomItem('GeneratorBucketCobblestone', CustomItemTypeNames::FACTIONS_GENERATOR_COBBLESTONE, null, ["nethergames:generator_cobblestone"]);
        self::registerSimpleCustomItem('GeneratorBucketObsidian', CustomItemTypeNames::FACTIONS_GENERATOR_OBSIDIAN, null, ["nethergames:generator_obsidian"]);
        self::registerSimpleCustomItem('GeneratorBucketBedrock', CustomItemTypeNames::FACTIONS_GENERATOR_BEDROCK, null, ["nethergames:generator_bedrock"]);
        self::registerSimpleCustomItem('PlayerHead', CustomItemTypeNames::FACTIONS_PLAYER_HEAD, null, ["nethergames:player_head"]);

        self::registerSimpleCustomItem('Landmine', CustomItemTypeNames::BEDWARS_LANDMINE);

        self::registerSimpleCustomItem('TeamSelectorWhite', CustomItemTypeNames::TEAM_SELECTOR_WHITE);
        self::registerSimpleCustomItem('TeamSelectorOrange', CustomItemTypeNames::TEAM_SELECTOR_ORANGE);
        self::registerSimpleCustomItem('TeamSelectorMagenta', CustomItemTypeNames::TEAM_SELECTOR_MAGENTA);
        self::registerSimpleCustomItem('TeamSelectorLightBlue', CustomItemTypeNames::TEAM_SELECTOR_LIGHT_BLUE);
        self::registerSimpleCustomItem('TeamSelectorYellow', CustomItemTypeNames::TEAM_SELECTOR_YELLOW);
        self::registerSimpleCustomItem('TeamSelectorLime', CustomItemTypeNames::TEAM_SELECTOR_LIME);
        self::registerSimpleCustomItem('TeamSelectorPink', CustomItemTypeNames::TEAM_SELECTOR_PINK);
        self::registerSimpleCustomItem('TeamSelectorGray', CustomItemTypeNames::TEAM_SELECTOR_GRAY);
        self::registerSimpleCustomItem('TeamSelectorLightGray', CustomItemTypeNames::TEAM_SELECTOR_LIGHT_GRAY);
        self::registerSimpleCustomItem('TeamSelectorCyan', CustomItemTypeNames::TEAM_SELECTOR_CYAN);
        self::registerSimpleCustomItem('TeamSelectorPurple', CustomItemTypeNames::TEAM_SELECTOR_PURPLE);
        self::registerSimpleCustomItem('TeamSelectorBlue', CustomItemTypeNames::TEAM_SELECTOR_BLUE);
        self::registerSimpleCustomItem('TeamSelectorBrown', CustomItemTypeNames::TEAM_SELECTOR_BROWN);
        self::registerSimpleCustomItem('TeamSelectorGreen', CustomItemTypeNames::TEAM_SELECTOR_GREEN);
        self::registerSimpleCustomItem('TeamSelectorRed', CustomItemTypeNames::TEAM_SELECTOR_RED);
        self::registerSimpleCustomItem('TeamSelectorBlack', CustomItemTypeNames::TEAM_SELECTOR_BLACK);

        foreach (
            [
                ItemTypeNames::IRON_GOLEM_SPAWN_EGG => "IronGolemSpawnEgg",
                ItemTypeNames::SKELETON_SPAWN_EGG => "SkeletonSpawnEgg",
                ItemTypeNames::TURTLE_SPAWN_EGG => "TurtleSpawnEgg"
            ] as $identifier => $name
        ) {
            self::registerItem(
                $name,
                $item = new Item(new ItemIdentifier(ItemTypeIds::newId())),
                $identifier,
                fn(Item $item) => new SavedItemData($identifier),
                fn(SavedItemData $data) => clone $item,
            );
        }
    }

    public static function TEAM_SELECTOR(DyeColor $color): SimpleCustomItem
    {
        /** @var SimpleCustomItem $item */
        $item = self::__callStatic('TEAM_SELECTOR_' . $color->name, []);

        return $item;
    }

    public static function getExperiments(): Experiments
    {
        return self::$experiment ??= new Experiments([
            "data_driven_items" => true,
            "cameras" => true
        ], false);
    }

    /**
     * @param ItemTypeEntry[]|null $mergedEntries
     *
     * @return ItemTypeEntry[]
     */
    public static function getItemTypeEntries(int $protocolId, ?array $mergedEntries = null): array
    {
        return array_values(array_map(function (ItemTypeEntry $entry) use ($protocolId): ItemTypeEntry {
            $itemComponent = self::$itemComponentEntries[$entry->getStringId()] ?? null;

            if ($itemComponent === null) {
                return $entry;
            }

            return new ItemTypeEntry(
                $entry->getStringId(),
                $entry->getNumericId(),
                true,
                $entry->getVersion(),
                new CacheableNbt($itemComponent->getComponents($protocolId)
                    ->setInt("id", $entry->getNumericId())
                    ->setString("name", $entry->getStringId())
                )
            );
        }, $mergedEntries ?? self::$itemTableEntries));
    }

    /**
     * @phpstan-param Closure(int, string): Item $factory
     */
    protected static function registerSimpleCustomItem(string $name, string $identifier, ?Closure $factory = null, array $bcDeserializers = [], array $components = []): void
    {
        $factory ??= function (int $id, string $name) use ($identifier, $components): Item {
            $item = new SimpleCustomItem(new ItemIdentifier($id), $name);
            $item->initComponent($identifier);
            foreach ($components as $component) {
                $item->addComponent($component);
            }
            return $item;
        };

        $item = $factory($itemId = ItemTypeIds::newId(), $name);
        self::registerCustomItem(
            $name,
            $item,
            $itemId,
            $identifier,
            fn(Item $item) => new SavedItemData($identifier),
            fn(SavedItemData $data) => clone $item,
            $bcDeserializers
        );
    }

    /**
     * @phpstan-param Closure(Item) : SavedItemData $serializer
     * @phpstan-param Closure(SavedItemData) : Item $deserializer
     * @phpstan-param list<string> $bcDeserializers
     */
    protected static function registerCustomItem(string $name, Item $item, int $itemId, string $identifier, Closure $serializer, Closure $deserializer, array $bcDeserializers = []): void
    {
        self::registerCustomItemMapping($identifier, $itemId);
        self::registerItem($name, $item, $identifier, $serializer, $deserializer, $bcDeserializers);

        if (($componentBased = $item instanceof ItemComponents)) {
            self::$itemComponentEntries[$identifier] = $item;
        }

        self::$itemTableEntries[$identifier] = new ItemTypeEntry($identifier, $itemId, $componentBased, 1, new CacheableNbt(new CompoundTag()));
    }

    /**
     * @phpstan-param Closure(Item) : SavedItemData $serializer
     * @phpstan-param Closure(SavedItemData) : Item $deserializer
     * @phpstan-param list<string> $bcDeserializers
     */
    protected static function registerItem(string $name, Item $item, string $identifier, Closure $serializer, Closure $deserializer, array $bcDeserializers = []): void
    {
        self::_registryRegister(Utils::pascalCaseToSnakeCase($name), $item);

        GlobalItemDataHandlers::getDeserializer()->map($identifier, $deserializer);
        foreach ($bcDeserializers as $bcDeserializer) {
            GlobalItemDataHandlers::getDeserializer()->map($bcDeserializer, $deserializer);
        }

        GlobalItemDataHandlers::getSerializer()->map($item, $serializer);
        StringToItemParser::getInstance()->override($identifier, fn() => clone $item);

        CreativeInventory::getInstance()->add($item);
    }

    public static function getItemComponentPacket(int $protocolId): ItemRegistryPacket
    {
        return ItemRegistryPacket::create(self::getItemTypeEntries(
            $protocolId,
            array_map(
                fn(string $identifier): ItemTypeEntry => self::$itemTableEntries[$identifier] ?? throw new InvalidArgumentException("Unknown item identifier: $identifier"),
                array_keys(self::$itemComponentEntries)
            )
        ));
    }

    private static function register(): void
    {
        foreach (TypeConverter::getAll() as $converter) {
            self::registerToTypeConverter($converter);
        }

        TypeConverter::addCreationListener(fn(TypeConverter $converter) => self::registerToTypeConverter($converter));
        self::$registered = true;
    }

    /**
     * Registers a custom item ID to the required mappings in the currently loaded ItemTypeDictionary instances.
     */
    private static function registerCustomItemMapping(string $identifier, int $itemId): void
    {
        self::$registeredItems[$identifier] = $itemId;

        if (self::$registered) {
            foreach (TypeConverter::getAll() as $converter) {
                self::registerCustomItemMappingToTypeConverter($identifier, $itemId, $converter);
            }
        } else {
            self::register();
        }
    }

    /**
     * Registers all current custom item IDs to the required mappings in the ItemTypeDictionary instance.
     */
    public static function registerToTypeConverter(TypeConverter $typeConverter): void
    {
        foreach (self::$registeredItems as $identifier => $itemId) {
            self::registerCustomItemMappingToTypeConverter($identifier, $itemId, $typeConverter);
        }
    }

    /**
     * Registers a custom item ID to the required mappings in the ItemTypeDictionary instance.
     */
    private static function registerCustomItemMappingToTypeConverter(string $identifier, int $itemId, TypeConverter $typeConverter): void
    {
        $dictionary = $typeConverter->getItemTypeDictionary();
        $reflection = new ReflectionClass($dictionary);

        $intToString = $reflection->getProperty("intToStringIdMap");
        /** @var int[] $value */
        $value = $intToString->getValue($dictionary);
        $intToString->setValue($dictionary, $value + [$itemId => $identifier]);

        $stringToInt = $reflection->getProperty("stringToIntMap");
        /** @var int[] $value */
        $value = $stringToInt->getValue($dictionary);
        $stringToInt->setValue($dictionary, $value + [$identifier => $itemId]);
    }

    /**
     * Registers the required mappings for the block to become an item that can be placed etc. It is assigned an ID that
     * correlates to its block ID.
     */
    public static function registerBlockItem(string $identifier, Block $block): void
    {
        $itemId = $block->getIdInfo()->getBlockTypeId();
        self::registerCustomItemMapping($identifier, $itemId);
        StringToItemParser::getInstance()->registerBlock($identifier, fn() => clone $block);
        self::$itemTableEntries[] = new ItemTypeEntry($identifier, $itemId, false, 2, new CacheableNbt(new CompoundTag()));

        $blockItemIdMap = BlockItemIdMap::getInstance();
        $reflection = new ReflectionClass($blockItemIdMap);

        $itemToBlockId = $reflection->getProperty("itemToBlockId");
        /** @var string[] $value */
        $value = $itemToBlockId->getValue($blockItemIdMap);
        $itemToBlockId->setValue($blockItemIdMap, $value + [$identifier => $identifier]);
    }
}