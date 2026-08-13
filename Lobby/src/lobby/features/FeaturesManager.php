<?php

declare(strict_types=1);

namespace lobby\features;

use lobby\features\crate\CratesHandler;
use lobby\features\maze\Maze;
use lobby\features\parkour\Parkour;
use lobby\features\presents\Presents;
use lobby\features\range\ShootingRange;
use lobby\features\secret\SecretTokens;
use lobby\features\zone\ZoneManager;
use lobby\utils\BaseTrait;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\Position;
use RuntimeException;

class FeaturesManager
{
    use BaseTrait;

    /** @var Maze */
    private Maze $maze;
    /** @var Parkour */
    private Parkour $parkour;
    /** @var CratesHandler */
    private CratesHandler $cratesHandler;
    /** @var ZoneManager */
    private ZoneManager $zoneManager;
    /** @var SecretTokens */
    private SecretTokens $tokens;
    /** @var Presents|null */
    private ?Presents $presents = null; // @phpstan-ignore property.unusedType
    /** @var ShootingRange[][] */
    private array $shootingRanges = [];

    public function __construct()
    {
        $plugin = $this->getPlugin();
        $default = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        $this->tokens = new SecretTokens();
        $this->tokens->prepareEntities($default);
        $this->maze = new Maze($plugin);
        $this->parkour = new Parkour();

        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(211, 55, 32, $default), new AxisAlignedBB(181, 55, 31, 206, 58, 34), $default));
        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(210.5, 55, 27.5, $default), new AxisAlignedBB(183, 55, 26, 206, 58, 29), $default));
        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(210.5, 55, 22.5, $default), new AxisAlignedBB(183, 55, 21, 206, 58, 24), $default));
        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(210.5, 55, 15.5, $default), new AxisAlignedBB(183, 55, 14, 206, 58, 17), $default));
        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(210.5, 55, 10.5, $default), new AxisAlignedBB(182, 55, 9, 206, 58, 13), $default));
        $this->addShootingRange(ShootingRange::FOREST_RANGE, new ShootingRange(new Position(210.5, 55, 6.5, $default), new AxisAlignedBB(182, 55, 4, 206, 58, 7), $default));

        $this->cratesHandler = new CratesHandler($this->getNGEssentials()->getPlayerManager()->getCosmeticHandler());
        $this->zoneManager = new ZoneManager();
        //$this->presents = new Presents($this);

        $plugin->getServer()->getPluginManager()->registerEvents(new FeatureListener($this), $plugin);
    }

    public function addShootingRange(int $categoryId, ShootingRange $shootingRange): void
    {
        if (array_key_exists($categoryId, $this->shootingRanges)) {
            $this->shootingRanges[$categoryId][] = $shootingRange;
        } else {
            $this->shootingRanges[$categoryId] = [$shootingRange];
        }
    }

    public function findFreeRange(int $categoryId): ?ShootingRange
    {
        if (!array_key_exists($categoryId, $this->shootingRanges)) throw new RuntimeException("No shooting range with id " . $categoryId . " exists");

        foreach ($this->shootingRanges[$categoryId] as $range) {
            if ($range->getPlayer() === null) {
                return $range;
            }
        }

        return null;
    }

    public function isUsingRange(Player $player): bool
    {
        foreach ($this->shootingRanges as $ranges) {
            foreach ($ranges as $range) {
                if ($range->getPlayer() === $player) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getMaze(): Maze
    {
        return $this->maze;
    }

    public function getParkour(): Parkour
    {
        return $this->parkour;
    }

    /**
     * @return range\ShootingRange[][]
     */
    public function getShootingRanges(): array
    {
        return $this->shootingRanges;
    }

    public function getCrateHandler(): CratesHandler
    {
        return $this->cratesHandler;
    }

    public function getZoneManager(): ZoneManager
    {
        return $this->zoneManager;
    }

    public function getTokens(): SecretTokens
    {
        return $this->tokens;
    }

    public function getPresents(): ?Presents
    {
        return $this->presents;
    }
}