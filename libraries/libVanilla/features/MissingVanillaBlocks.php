<?php
declare(strict_types=1);

namespace libVanilla\features;

use libVanilla\block\BambooBlock;
use libVanilla\block\BambooButton;
use libVanilla\block\BambooDoor;
use libVanilla\block\BambooFence;
use libVanilla\block\BambooFenceGate;
use libVanilla\block\BambooMosaic;
use libVanilla\block\BambooMosaicSlab;
use libVanilla\block\BambooMosaicStairs;
use libVanilla\block\BambooPlanks;
use libVanilla\block\BambooPressurePlate;
use libVanilla\block\BambooSlab;
use libVanilla\block\BambooStairs;
use libVanilla\block\BambooTrapdoor;
use libVanilla\block\Beehive;
use libVanilla\block\BeeNest;
use libVanilla\block\Bush;
use libVanilla\block\CherrySapling;
use libVanilla\block\Composter;
use libVanilla\block\CopperGolemStatue;
use libVanilla\block\DecoratedPot;
use libVanilla\block\EndPortal;
use libVanilla\block\FireflyBush;
use libVanilla\block\Grindstone;
use libVanilla\block\LeafLitter;
use libVanilla\block\MossCarpet;
use libVanilla\block\ShortDryGrass;
use libVanilla\block\TallDryGrass;
use libVanilla\block\tile\TileCopperGolemStatue;
use libVanilla\block\tile\TileDecoratedPot;
use libVanilla\block\Wildflowers;
use libVanilla\tasks\VanillaBlockWorkerTask;
use libVanilla\utils\BlockRegistrationHelper;
use pocketmine\block\BlockBreakInfo as BBI;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeInfo as BTI;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Opaque;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\utils\WoodType;
use pocketmine\data\bedrock\block\BlockTypeNames as BTN;
use pocketmine\plugin\PluginBase;

class MissingVanillaBlocks extends Feature
{
    /**
     * All registered blocks as [id => block] for worker thread propagation.
     * @var array<string, \pocketmine\block\Block>
     */
    private static array $registeredBlocks = [];

    private ?\Closure $workerStartHook = null;

    public function register(PluginBase $plugin): void
    {
        if ($this->registered) {
            return;
        }
        $this->setup($plugin);
        $this->registered = true;
    }

    protected function setup(PluginBase $plugin): void
    {
        $this->registerTileEntities();
        $this->registerAllBlocks();
        $this->registerBlocksOnWorkers($plugin);
    }

    /**
     * Get all registered blocks for worker thread propagation.
     * @return array<string, \pocketmine\block\Block>
     */
    public static function getRegisteredBlocks(): array
    {
        return self::$registeredBlocks;
    }

    private function registerTileEntities(): void
    {
        $factory = TileFactory::getInstance();
        try {
            $factory->register(TileDecoratedPot::class, ["DecoratedPot", "minecraft:decorated_pot"]);
        } catch (\InvalidArgumentException $e) {
            // Already registered
        }
        try {
            $factory->register(TileCopperGolemStatue::class, ["CopperGolemStatue", "minecraft:copper_golem_statue"]);
        } catch (\InvalidArgumentException $e) {
            // Already registered
        }
    }

    private function registerAllBlocks(): void
    {
        $woodInfo = BlockRegistrationHelper::woodLikeInfo();

        // (contents copied from previous implementation)
        // Block of Bamboo (log equivalent but uses custom class for stripping behavior)
        $bambooBlock = new BambooBlock(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Block of Bamboo",
            $woodInfo,
            WoodType::OAK
        );
        BlockRegistrationHelper::registerFull($bambooBlock, BTN::BAMBOO_BLOCK);
        self::$registeredBlocks[BTN::BAMBOO_BLOCK] = $bambooBlock;

        // Stripped Block of Bamboo
        $strippedBambooBlock = (new BambooBlock(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Stripped Block of Bamboo",
            $woodInfo,
            WoodType::OAK
        ))->setStripped(true);
        BlockRegistrationHelper::registerFull($strippedBambooBlock, BTN::STRIPPED_BAMBOO_BLOCK);
        self::$registeredBlocks[BTN::STRIPPED_BAMBOO_BLOCK] = $strippedBambooBlock;

        // Bamboo Planks
        $bambooPlanks = new BambooPlanks(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Planks", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooPlanks, BTN::BAMBOO_PLANKS);
        self::$registeredBlocks[BTN::BAMBOO_PLANKS] = $bambooPlanks;

        // Bamboo Mosaic
        $bambooMosaic = new BambooMosaic(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Mosaic", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooMosaic, BTN::BAMBOO_MOSAIC);
        self::$registeredBlocks[BTN::BAMBOO_MOSAIC] = $bambooMosaic;

        // Bamboo Stairs
        $bambooStairs = new BambooStairs(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Stairs", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooStairs, BTN::BAMBOO_STAIRS);
        self::$registeredBlocks[BTN::BAMBOO_STAIRS] = $bambooStairs;

        // Bamboo Mosaic Stairs
        $bambooMosaicStairs = new BambooMosaicStairs(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Mosaic Stairs", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooMosaicStairs, BTN::BAMBOO_MOSAIC_STAIRS);
        self::$registeredBlocks[BTN::BAMBOO_MOSAIC_STAIRS] = $bambooMosaicStairs;

        // Bamboo Slab
        $bambooSlab = new BambooSlab(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Slab", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooSlab, BTN::BAMBOO_SLAB);
        self::$registeredBlocks[BTN::BAMBOO_SLAB] = $bambooSlab;

        // Bamboo Mosaic Slab
        $bambooMosaicSlab = new BambooMosaicSlab(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Mosaic Slab", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooMosaicSlab, BTN::BAMBOO_MOSAIC_SLAB);
        self::$registeredBlocks[BTN::BAMBOO_MOSAIC_SLAB] = $bambooMosaicSlab;

        // Bamboo Fence
        $bambooFence = new BambooFence(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Fence", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooFence, BTN::BAMBOO_FENCE);
        self::$registeredBlocks[BTN::BAMBOO_FENCE] = $bambooFence;

        // Bamboo Fence Gate
        $bambooFenceGate = new BambooFenceGate(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Bamboo Fence Gate",
            $woodInfo,
            WoodType::OAK
        );
        BlockRegistrationHelper::registerFull($bambooFenceGate, BTN::BAMBOO_FENCE_GATE);
        self::$registeredBlocks[BTN::BAMBOO_FENCE_GATE] = $bambooFenceGate;

        // Bamboo Door
        $bambooDoor = new BambooDoor(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Door", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooDoor, BTN::BAMBOO_DOOR);
        self::$registeredBlocks[BTN::BAMBOO_DOOR] = $bambooDoor;

        // Bamboo Trapdoor
        $bambooTrapdoor = new BambooTrapdoor(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Trapdoor", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooTrapdoor, BTN::BAMBOO_TRAPDOOR);
        self::$registeredBlocks[BTN::BAMBOO_TRAPDOOR] = $bambooTrapdoor;

        // Bamboo Button
        $bambooButton = new BambooButton(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Button", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooButton, BTN::BAMBOO_BUTTON);
        self::$registeredBlocks[BTN::BAMBOO_BUTTON] = $bambooButton;

        // Bamboo Pressure Plate
        $bambooPressurePlate = new BambooPressurePlate(new BID(BlockRegistrationHelper::allocateTypeId()), "Bamboo Pressure Plate", $woodInfo);
        BlockRegistrationHelper::registerFull($bambooPressurePlate, BTN::BAMBOO_PRESSURE_PLATE);
        self::$registeredBlocks[BTN::BAMBOO_PRESSURE_PLATE] = $bambooPressurePlate;

        // Lodestone
        $lodestone = new Opaque(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Lodestone",
            new BTI(new BBI(2))
        );
        BlockRegistrationHelper::registerFull($lodestone, BTN::LODESTONE);
        self::$registeredBlocks[BTN::LODESTONE] = $lodestone;

        // Composter
        $composter = new Composter(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Composter",
            new BTI(new BBI(0.6, BlockToolType::AXE))
        );
        BlockRegistrationHelper::registerFull($composter, BTN::COMPOSTER);
        self::$registeredBlocks[BTN::COMPOSTER] = $composter;

        // Decorated Pot
        $decoratedPot = new DecoratedPot(
            new BID(BlockRegistrationHelper::allocateTypeId(), TileDecoratedPot::class),
            "Decorated Pot",
            new BTI(new BBI(0))
        );
        BlockRegistrationHelper::registerFull($decoratedPot, BTN::DECORATED_POT);
        self::$registeredBlocks[BTN::DECORATED_POT] = $decoratedPot;

        // Grindstone
        $grindstone = new Grindstone(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Grindstone",
            new BTI(new BBI(2, BlockToolType::PICKAXE, 0, 30))
        );
        BlockRegistrationHelper::registerFull($grindstone, BTN::GRINDSTONE);
        self::$registeredBlocks[BTN::GRINDSTONE] = $grindstone;

        // Copper Golem Statues (8 variants)
        $copperGolemInfo = new BTI(new BBI(3, BlockToolType::PICKAXE, 0, 30));
        $copperGolemVariants = [
            BTN::COPPER_GOLEM_STATUE => "Copper Golem Statue",
            BTN::EXPOSED_COPPER_GOLEM_STATUE => "Exposed Copper Golem Statue",
            BTN::WEATHERED_COPPER_GOLEM_STATUE => "Weathered Copper Golem Statue",
            BTN::OXIDIZED_COPPER_GOLEM_STATUE => "Oxidized Copper Golem Statue",
            BTN::WAXED_COPPER_GOLEM_STATUE => "Waxed Copper Golem Statue",
            BTN::WAXED_EXPOSED_COPPER_GOLEM_STATUE => "Waxed Exposed Copper Golem Statue",
            BTN::WAXED_WEATHERED_COPPER_GOLEM_STATUE => "Waxed Weathered Copper Golem Statue",
            BTN::WAXED_OXIDIZED_COPPER_GOLEM_STATUE => "Waxed Oxidized Copper Golem Statue",
        ];
        foreach ($copperGolemVariants as $btnId => $name) {
            $block = new CopperGolemStatue(
                new BID(BlockRegistrationHelper::allocateTypeId(), TileCopperGolemStatue::class),
                $name,
                $copperGolemInfo
            );
            BlockRegistrationHelper::registerFull($block, $btnId);
            self::$registeredBlocks[$btnId] = $block;
        }

        // Bee Nest
        $beeNest = new BeeNest(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Bee Nest",
            new BTI(new BBI(0.3, BlockToolType::AXE, 0, 0.3))
        );
        BlockRegistrationHelper::registerFull($beeNest, BTN::BEE_NEST);
        self::$registeredBlocks[BTN::BEE_NEST] = $beeNest;

        // Beehive
        $beehive = new Beehive(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Beehive",
            new BTI(new BBI(0.6, BlockToolType::AXE, 0, 0.6))
        );
        BlockRegistrationHelper::registerFull($beehive, BTN::BEEHIVE);
        self::$registeredBlocks[BTN::BEEHIVE] = $beehive;

        // Bush
        $bush = new Bush(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Bush",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($bush, BTN::BUSH);
        self::$registeredBlocks[BTN::BUSH] = $bush;

        // Firefly Bush
        $fireflyBush = new FireflyBush(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Firefly Bush",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($fireflyBush, BTN::FIREFLY_BUSH);
        self::$registeredBlocks[BTN::FIREFLY_BUSH] = $fireflyBush;

        // Wildflowers
        $wildflowers = new Wildflowers(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Wildflowers",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($wildflowers, BTN::WILDFLOWERS);
        self::$registeredBlocks[BTN::WILDFLOWERS] = $wildflowers;

        // Leaf Litter
        $leafLitter = new LeafLitter(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Leaf Litter",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($leafLitter, BTN::LEAF_LITTER);
        self::$registeredBlocks[BTN::LEAF_LITTER] = $leafLitter;

        // Cherry Sapling
        $cherrySapling = new CherrySapling(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Cherry Sapling",
            new BTI(BBI::instant(), [BlockTypeTags::POTTABLE_PLANTS])
        );
        BlockRegistrationHelper::registerFull($cherrySapling, BTN::CHERRY_SAPLING);
        self::$registeredBlocks[BTN::CHERRY_SAPLING] = $cherrySapling;

        // Moss Block
        $mossBreakInfo = new BTI(BBI::hoe(0.1));
        $mossBlock = new Opaque(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Moss Block",
            $mossBreakInfo
        );
        BlockRegistrationHelper::registerFull($mossBlock, BTN::MOSS_BLOCK);
        self::$registeredBlocks[BTN::MOSS_BLOCK] = $mossBlock;

        // Moss Carpet
        $mossCarpet = new MossCarpet(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Moss Carpet",
            $mossBreakInfo
        );
        BlockRegistrationHelper::registerFull($mossCarpet, BTN::MOSS_CARPET);
        self::$registeredBlocks[BTN::MOSS_CARPET] = $mossCarpet;

        // Dripstone Block
        $dripstoneBlock = new Opaque(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Dripstone Block",
            new BTI(new BBI(1.5, BlockToolType::PICKAXE, 0, 5.0))
        );
        BlockRegistrationHelper::registerFull($dripstoneBlock, BTN::DRIPSTONE_BLOCK);
        self::$registeredBlocks[BTN::DRIPSTONE_BLOCK] = $dripstoneBlock;

        // Short Dry Grass
        $shortDryGrass = new ShortDryGrass(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Short Dry Grass",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($shortDryGrass, BTN::SHORT_DRY_GRASS);
        self::$registeredBlocks[BTN::SHORT_DRY_GRASS] = $shortDryGrass;

        // Tall Dry Grass
        $tallDryGrass = new TallDryGrass(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "Tall Dry Grass",
            new BTI(BBI::instant())
        );
        BlockRegistrationHelper::registerFull($tallDryGrass, BTN::TALL_DRY_GRASS);
        self::$registeredBlocks[BTN::TALL_DRY_GRASS] = $tallDryGrass;

        // End Portal
        $endPortal = new EndPortal(
            new BID(BlockRegistrationHelper::allocateTypeId()),
            "End Portal",
            new BTI(BBI::indestructible())
        );
        BlockRegistrationHelper::registerBlock($endPortal);
        BlockRegistrationHelper::mapBlockSerializerDeserializer($endPortal, BTN::END_PORTAL);
        self::$registeredBlocks[BTN::END_PORTAL] = $endPortal;
    }

    private function registerBlocksOnWorkers(PluginBase $plugin): void
    {
        $asyncPool = $plugin->getServer()->getAsyncPool();

        foreach ($asyncPool->getRunningWorkers() as $workerId) {
            $asyncPool->submitTaskToWorker(new VanillaBlockWorkerTask(), $workerId);
        }

        $this->workerStartHook = function (int $workerId) use ($asyncPool): void {
            $asyncPool->submitTaskToWorker(new VanillaBlockWorkerTask(), $workerId);
        };
        $asyncPool->addWorkerStartHook($this->workerStartHook);
    }
}
