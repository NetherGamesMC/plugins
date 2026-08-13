<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block;

use Closure;
use NetherGames\NGEssentials\block\custom\LuckyBlock;
use NetherGames\NGEssentials\block\permutations\Permutable;
use NetherGames\NGEssentials\block\permutations\Permutation;
use NetherGames\NGEssentials\block\permutations\Permutations;
use NetherGames\NGEssentials\item\CreativeInventoryInfo;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo as Info;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\ToolTier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

/**
 * @method static LuckyBlock LUCKY_BLOCK()
 */
final class CustomBlockRegistry
{
    use CloningRegistryTrait;

    /**
     * @var Closure[]
     * @phpstan-var array<string, array{(Closure(int): Block), string, (Closure(BlockStateWriter): Block), (Closure(Block): BlockStateReader)}>
     */
    private static array $blockFuncs = [];
    /** @var BlockPaletteEntry[] */
    private static array $blockPaletteEntries = [];

    public static function forceInitialization(): void
    {
        self::checkInit();
    }

    protected static function registerLuckyBlock(): void
    {
        self::registerBlock(static fn() => new LuckyBlock(
            new BID(BlockTypeIds::newId()),
            "LuckyBlock",
            new Info(BreakInfo::pickaxe(0.2, ToolTier::STONE, 30.0)),
        ), "LuckyBlock", "ng:skywars_lucky_block", new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_CONSTRUCTION, CreativeInventoryInfo::NONE));
    }

    protected static function setup(): void
    {
        //self::registerLuckyBlock();
    }

    public static function modifyStartGamePacket(StartGamePacket $packet): void
    {
        $packet->blockPalette = array_merge($packet->blockPalette, self::$blockPaletteEntries);
    }

    /**
     * Register a block to the BlockFactory and all the required mappings. A custom stateReader and stateWriter can be
     * provided to allow for custom block state serialization.
     * @phpstan-param (Closure(): Block) $blockFunc
     * @phpstan-param null|(Closure(BlockStateWriter): Block) $serializer
     * @phpstan-param null|(Closure(Block): BlockStateReader) $deserializer
     */
    public static function registerBlock(Closure $blockFunc, string $name, string $identifier, ?CreativeInventoryInfo $creativeInfo = null, ?Closure $serializer = null, ?Closure $deserializer = null, bool $registerToRegistry = true): Block
    {
        $block = $blockFunc();

        RuntimeBlockStateRegistry::getInstance()->register($block);
        CustomItemRegistry::registerBlockItem($identifier, $block);

        $propertiesTag = CompoundTag::create();
        $components = CompoundTag::create();
        if ($block instanceof BlockComponents) {
            foreach ($block->getComponents() as $component) {
                $components->setTag($component->getName(), $component->getValue());
            }
        }

        if ($block instanceof Permutable) {
            $blockPropertyNames = $blockPropertyValues = $blockProperties = [];
            foreach ($block->getBlockProperties() as $blockProperty) {
                $blockPropertyNames[] = $blockProperty->getName();
                $blockPropertyValues[] = $blockProperty->getValues();
                $blockProperties[] = $blockProperty->toNBT();
            }
            $permutations = array_map(static fn(Permutation $permutation) => $permutation->toNBT(), $block->getPermutations());

            // The 'minecraft:on_player_placing' component is required for the client to predict block placement, making
            // it a smoother experience for the end-user.
            $components->setTag("minecraft:on_player_placing", CompoundTag::create());
            $propertiesTag
                ->setTag("permutations", new ListTag($permutations))
                ->setTag("properties", new ListTag(array_reverse($blockProperties))); // fix client-side order

            foreach (Permutations::getCartesianProduct($blockPropertyValues) as $meta => $permutations) {
                // We need to insert states for every possible permutation to allow for all blocks to be used and to
                // keep in sync with the client's block palette.
                $states = CompoundTag::create();
                foreach ($permutations as $i => $value) {
                    $states->setTag($blockPropertyNames[$i], $value);
                }

                $blockState = CompoundTag::create()
                    ->setString(BlockStateData::TAG_NAME, $identifier)
                    ->setTag(BlockStateData::TAG_STATES, $states);
                BlockPalette::insertState($blockState, $meta);
            }

            $serializer ??= static function (Block $block) use ($identifier): BlockStateWriter {
                /** @var Block&Permutable $block */
                $b = BlockStateWriter::create($identifier);
                $block->serializeState($b);
                return $b;
            };
            $deserializer ??= static function (BlockStateReader $in) use ($block): Permutable {
                $b = clone $block;
                $b->deserializeState($in);
                return $b;
            };
        } else {
            // If a block does not contain any permutations we can just insert the one state.
            $blockState = CompoundTag::create()
                ->setString(BlockStateData::TAG_NAME, $identifier)
                ->setTag(BlockStateData::TAG_STATES, CompoundTag::create());

            BlockPalette::insertState($blockState);

            $serializer ??= static fn() => new BlockStateWriter($identifier);
            $deserializer ??= static fn(BlockStateReader $in) => $block;
        }

        GlobalBlockStateHandlers::getSerializer()->map($block, $serializer);
        GlobalBlockStateHandlers::getDeserializer()->map($identifier, $deserializer);

        $addToCreative = $creativeInfo !== null;

        $creativeInfo ??= CreativeInventoryInfo::DEFAULT();
        $components->setTag("minecraft:creative_category", CompoundTag::create()
            ->setString("category", $creativeInfo->getCategory())
            ->setString("group", $creativeInfo->getGroup()));
        $propertiesTag
            ->setTag("components",
                $components->setTag("minecraft:creative_category", CompoundTag::create()
                    ->setString("category", $creativeInfo->getCategory())
                    ->setString("group", $creativeInfo->getGroup())))
            ->setTag("menu_category", CompoundTag::create()
                ->setString("category", $creativeInfo->getCategory())
                ->setString("group", $creativeInfo->getGroup()))
            ->setInt("molangVersion", 1);

        if ($addToCreative) {
            CreativeInventory::getInstance()->add($block->asItem(), match ($creativeInfo->getCategory()) {
                CreativeInventoryInfo::CATEGORY_CONSTRUCTION => CreativeCategory::CONSTRUCTION,
                CreativeInventoryInfo::CATEGORY_ITEMS => CreativeCategory::ITEMS,
                CreativeInventoryInfo::CATEGORY_NATURE => CreativeCategory::NATURE,
                CreativeInventoryInfo::CATEGORY_EQUIPMENT => CreativeCategory::EQUIPMENT,
                default => throw new AssumptionFailedError("Unknown category")
            });
        }

        self::$blockPaletteEntries[] = new BlockPaletteEntry($identifier, new CacheableNbt($propertiesTag));
        self::$blockFuncs[$identifier] = [$blockFunc, $name, $serializer, $deserializer];
        if ($registerToRegistry) {
            self::_registryRegister(Utils::pascalCaseToSnakeCase($name), $block);
        }

        usort(self::$blockPaletteEntries, static function (BlockPaletteEntry $a, BlockPaletteEntry $b): int {
            return strcmp(hash("fnv164", $a->getName()), hash("fnv164", $b->getName()));
        });
        foreach (self::$blockPaletteEntries as $i => $entry) {
            $root = $entry->getStates()->getRoot()
                ->setTag("vanilla_block_data", CompoundTag::create()
                    ->setInt("block_id", 10000 + $i));
            self::$blockPaletteEntries[$i] = new BlockPaletteEntry($entry->getName(), new CacheableNbt($root));
        }

        return $block;
    }

    /**
     * @return array<string, array{(Closure(int): Block), string, (Closure(BlockStateWriter): Block), (Closure(Block): BlockStateReader)}>
     * @internal
     */
    public static function getBlockFunctions(): array
    {
        return self::$blockFuncs;
    }
}
