<?php
declare(strict_types=1);

namespace libVanilla\utils;

use libVanilla\block\Beehive;
use libVanilla\block\BeeNest;
use libVanilla\block\CherrySapling;
use libVanilla\block\Composter;
use libVanilla\block\CopperGolemStatue;
use libVanilla\block\DecoratedPot;
use libVanilla\block\Grindstone;
use libVanilla\block\LeafLitter;
use libVanilla\block\Wildflowers;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Button;
use pocketmine\block\Door;
use pocketmine\block\DoublePlant;
use pocketmine\block\FenceGate;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\SimplePressurePlate;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\Trapdoor;
use pocketmine\block\WeightedPressurePlate;
use pocketmine\block\Wood;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateNames as BSN;
use pocketmine\data\bedrock\block\convert\Model;
use pocketmine\data\bedrock\block\convert\property\BoolProperty;
use pocketmine\data\bedrock\block\convert\property\CommonProperties;
use pocketmine\data\bedrock\block\convert\property\IntFromRawStateMap;
use pocketmine\data\bedrock\block\convert\property\IntProperty;
use pocketmine\data\bedrock\block\convert\property\ValueFromStringProperty;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use ReflectionClass;
use ReflectionProperty;

/**
 * Helper class for registering custom blocks in plugins.
 * Provides reusable methods for block registration, serialization, and creative inventory management.
 */
final class BlockRegistrationHelper
{
    private static ?ReflectionProperty $typeIndexProperty = null;
    private static ?ReflectionProperty $serializersProperty = null;

    /**
     * Create a BlockTypeInfo with standard break info for wood-like blocks.
     */
    public static function woodLikeInfo(float $hardness = 2.0, float $blastResistance = 15.0): BlockTypeInfo
    {
        return new BlockTypeInfo(new BlockBreakInfo($hardness, BlockToolType::AXE, 0, $blastResistance));
    }

    /**
     * Get the reflection property for RuntimeBlockStateRegistry::$typeIndex
     */
    private static function getTypeIndexProperty(): ReflectionProperty
    {
        if (self::$typeIndexProperty === null) {
            $refClass = new ReflectionClass(RuntimeBlockStateRegistry::class);
            self::$typeIndexProperty = $refClass->getProperty("typeIndex");
        }
        return self::$typeIndexProperty;
    }

    /**
     * Get the reflection property for BlockObjectToStateSerializer::$serializers
     */
    private static function getSerializersProperty(): ReflectionProperty
    {
        if (self::$serializersProperty === null) {
            $serializer = GlobalBlockStateHandlers::getSerializer();
            $refClass = new ReflectionClass($serializer);
            self::$serializersProperty = $refClass->getProperty("serializers");
        }
        return self::$serializersProperty;
    }

    /**
     * Unregister a block type from the RuntimeBlockStateRegistry.
     * This is necessary before registering a custom implementation with the same type ID.
     *
     * @param int $typeId The block type ID to unregister
     */
    public static function unregisterBlockType(int $typeId): void
    {
        $registry = RuntimeBlockStateRegistry::getInstance();
        $property = self::getTypeIndexProperty();
        $typeIndex = $property->getValue($registry);
        unset($typeIndex[$typeId]);
        $property->setValue($registry, $typeIndex);
    }

    /**
     * Register a block with the RuntimeBlockStateRegistry and StringToItemParser.
     *
     * @param Block $block The block to register
     */
    public static function registerBlock(Block $block): void
    {
        RuntimeBlockStateRegistry::getInstance()->register($block);
        self::registerStringToItemMapping($block);
    }

    /**
     * Register a block name mapping in StringToItemParser so the block can be given via commands.
     *
     * @param Block $block The block to register
     */
    public static function registerStringToItemMapping(Block $block): void
    {
        try {
            StringToItemParser::getInstance()->registerBlock($block->getName(), fn(string $input) => clone $block);
        } catch (\InvalidArgumentException) {
            StringToItemParser::getInstance()->override($block->getName(), fn(string $input) => (clone $block)->asItem());
        }
    }

    /**
     * Register a block serializer mapping.
     *
     * @param Block $block The block to map
     * @param \Closure|BlockStateData $serializer The serializer closure or static BlockStateData
     */
    public static function registerSerializer(Block $block, \Closure|BlockStateData $serializer): void
    {
        $serializerInstance = GlobalBlockStateHandlers::getSerializer();

        // Check if already registered, unregister if needed
        $property = self::getSerializersProperty();
        $serializers = $property->getValue($serializerInstance);

        if (isset($serializers[$block->getTypeId()])) {
            unset($serializers[$block->getTypeId()]);
            $property->setValue($serializerInstance, $serializers);
        }

        $serializerInstance->map($block, $serializer);
    }

    /**
     * Add a block's item to the creative inventory.
     *
     * @param Block $block The block whose item to add
     * @param string $category The creative category (e.g., "itemGroup.name.planks", "itemGroup.name.nature")
     */
    public static function addToCreativeInventory(Block $block, string $category = "itemGroup.name.planks"): void
    {
        $item = $block->asItem();
        if ($item->isNull()) {
            return;
        }

        CreativeInventory::getInstance()->add($item);
    }

    /**
     * Add an item to the creative inventory.
     *
     * @param Item $item The item to add
     */
    public static function addItemToCreativeInventory(Item $item): void
    {
        CreativeInventory::getInstance()->add($item);
    }

    /**
     * Allocate a new block type ID.
     * Uses BlockTypeIds::newId() to get a unique ID.
     *
     * @return int The new block type ID
     */
    public static function allocateTypeId(): int
    {
        return BlockTypeIds::newId();
    }

    /**
     * Full registration: runtime + serializer/deserializer via registrar + StringToItemParser + creative inventory.
     *
     * @param Block $block The block to register
     * @param string $id The bedrock block type name (e.g. "minecraft:bamboo_stairs")
     */
    public static function registerFull(Block $block, string $id): void
    {
        self::registerBlock($block);
        self::mapBlockSerializerDeserializer($block, $id);
        self::addToCreativeInventory($block);
    }

    /**
     * Map serializer and deserializer for a block based on its type using the registrar API.
     * The registrar handles both serialization AND deserialization internally, including on worker threads.
     *
     * @param Block $block The block to map
     * @param string $id The block state ID (e.g., "minecraft:bamboo_stairs")
     */
    public static function mapBlockSerializerDeserializer(Block $block, string $id): void
    {
        $registrar = GlobalBlockStateHandlers::getRegistrar();
        $commonProps = CommonProperties::getInstance();

        if ($block instanceof Stair) {
            $registrar->mapStairs($block, $id);
        } elseif ($block instanceof Slab) {
            $parts = explode(":", $id);
            if (count($parts) === 2) {
                $materialName = str_replace(["_slab", "_double_slab"], "", $parts[1]);
                $registrar->mapSlab($block, $materialName);
            }
        } elseif ($block instanceof Wood) {
            $registrar->mapModel(Model::create($block, $id)->properties([$commonProps->pillarAxis]));
        } elseif ($block instanceof Door) {
            $registrar->mapModel(Model::create($block, $id)->properties($commonProps->doorProperties));
        } elseif ($block instanceof Trapdoor) {
            $registrar->mapModel(Model::create($block, $id)->properties($commonProps->trapdoorProperties));
        } elseif ($block instanceof FenceGate) {
            $registrar->mapModel(Model::create($block, $id)->properties($commonProps->fenceGateProperties));
        } elseif ($block instanceof SimplePressurePlate) {
            $registrar->mapModel(Model::create($block, $id)->properties($commonProps->simplePressurePlateProperties));
        } elseif ($block instanceof WeightedPressurePlate) {
            $registrar->mapModel(Model::create($block, $id)->properties([$commonProps->analogRedstoneSignal]));
        } elseif ($block instanceof Button) {
            $registrar->mapModel(Model::create($block, $id)->properties($commonProps->buttonProperties));
        } elseif ($block instanceof CopperGolemStatue) {
            $registrar->mapModel(Model::create($block, $id)->properties([$commonProps->horizontalFacingCardinal]));
        } elseif ($block instanceof DecoratedPot) {
            $registrar->mapModel(Model::create($block, $id)->properties([$commonProps->horizontalFacingSWNE]));
        } elseif ($block instanceof Composter) {
            $registrar->mapModel(Model::create($block, $id)->properties([
                new IntProperty(BSN::COMPOSTER_FILL_LEVEL, 0, 8,
                    fn($b) => $b->getFillLevel(),
                    fn($b, int $v) => $b->setFillLevel($v)
                )
            ]));
        } elseif ($block instanceof Grindstone) {
            $attachmentMap = IntFromRawStateMap::string([
                Grindstone::ATTACHMENT_STANDING => 'standing',
                Grindstone::ATTACHMENT_HANGING => 'hanging',
                Grindstone::ATTACHMENT_SIDE => 'side',
            ]);
            $attachmentProp = new ValueFromStringProperty(
                BSN::ATTACHMENT,
                $attachmentMap,
                fn($b) => $b->getAttachment(),
                fn($b, int $v) => $b->setAttachment($v)
            );
            $registrar->mapModel(Model::create($block, $id)->properties([
                $attachmentProp,
                $commonProps->horizontalFacingSWNE
            ]));
        } elseif ($block instanceof BeeNest || $block instanceof Beehive) {
            $registrar->mapModel(Model::create($block, $id)->properties([
                $commonProps->horizontalFacingSWNE,
                new IntProperty(BSN::HONEY_LEVEL, 0, 5,
                    fn($b) => $b->getHoneyLevel(),
                    fn($b, int $v) => $b->setHoneyLevel($v)
                )
            ]));
        } elseif ($block instanceof Wildflowers || $block instanceof LeafLitter) {
            $registrar->mapModel(Model::create($block, $id)->properties([
                new IntProperty(BSN::GROWTH, 0, 7,
                    fn($b) => $b->getCount(),
                    fn($b, int $v) => $b->setCount(min($v, $block::MAX_COUNT)),
                    offset: 1
                ),
                $commonProps->horizontalFacingCardinal
            ]));
        } elseif ($block instanceof CherrySapling) {
            $registrar->mapModel(Model::create($block, $id)->properties([
                new BoolProperty(BSN::AGE_BIT,
                    fn($b) => $b->isReady(),
                    fn($b, bool $v) => $b->setReady($v)
                )
            ]));
        } elseif ($block instanceof DoublePlant) {
            $registrar->mapModel(Model::create($block, $id)->properties([$commonProps->doublePlantHalf]));
        } else {
            $registrar->mapSimple($block, $id);
        }
    }

    /**
     * Complete block registration helper: unregister old (if exists), register new block, register serializer, add to creative inventory.
     *
     * @param Block $block The block to register
     * @param \Closure|BlockStateData $serializer The serializer for block state conversion
     * @param bool $addToCreative Whether to add the block to creative inventory (default: true)
     * @param int|null $replaceTypeId If provided, unregisters this type ID before registering the new block
     * @deprecated Use registerFull() with the registrar-based approach instead
     */
    public static function registerBlockComplete(
        Block                   $block,
        \Closure|BlockStateData $serializer,
        bool                    $addToCreative = true,
        ?int                    $replaceTypeId = null
    ): void
    {
        if ($replaceTypeId !== null) {
            self::unregisterBlockType($replaceTypeId);
        }

        self::registerBlock($block);
        self::registerSerializer($block, $serializer);

        if ($addToCreative) {
            self::addToCreativeInventory($block);
        }
    }
}
