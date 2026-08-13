<?php

declare(strict_types=1);

namespace libVanilla\block\behaviour;

use pocketmine\block\tile\BlastFurnace;
use pocketmine\block\tile\EnderChest;
use pocketmine\block\tile\NormalFurnace;
use pocketmine\block\tile\Smoker;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\crafting\FurnaceType;
use pocketmine\utils\Utils;

final class HopperBehaviourManager
{
    /** @var HopperBehaviour[] */
    private static array $behaviours = [];
    /** @var HopperBehaviour */
    private static HopperBehaviour $fallback;
    /** @var string[] */
    private static array $cache = [];

    public static function registerDefaults(): void
    {
        self::registerFallback(DefaultHopperBehaviour::getInstance());
        self::register(EnderChest::class, ImmobileHopperBehaviour::getInstance());
        self::register(NormalFurnace::class, new FurnaceHopperBehaviour(FurnaceType::FURNACE));
        self::register(BlastFurnace::class, new FurnaceHopperBehaviour(FurnaceType::BLAST_FURNACE));
        self::register(Smoker::class, new FurnaceHopperBehaviour(FurnaceType::SMOKER));
    }

    public static function registerFallback(HopperBehaviour $behaviour): void
    {
        self::$fallback = $behaviour;
    }

    /**
     * @param string $tile_class
     * @param HopperBehaviour $behaviour
     *
     * @phpstan-param class-string<Tile> $tile_class
     */
    public static function register(string $tile_class, HopperBehaviour $behaviour): void
    {
        Utils::testValidInstance($tile_class, Tile::class);
        self::$behaviours[$tile_class] = $behaviour;
        self::$cache = [];
    }

    public static function getFromTile(Tile $tile): HopperBehaviour
    {
        if (!isset(self::$cache[$class = get_class($tile)])) {
            $tileFactory = TileFactory::getInstance();
            $tile_save_id = $tileFactory->getSaveId($class);
            /**
             * @phpstan-var class-string<Tile> $tile_class
             */
            foreach (self::$behaviours as $tile_class => $_) {
                if ($tileFactory->getSaveId($tile_class) === $tile_save_id) {
                    self::$cache[$class] = $tile_class;
                    break;
                }
            }
        }

        return isset(self::$cache[$class]) ? self::get(self::$cache[$class]) : self::$fallback;
    }

    public static function get(string $tile_class): HopperBehaviour
    {
        return self::$behaviours[$tile_class] ?? self::$fallback;
    }
}