<?php

namespace meltdown\arena\handler;

use pocketmine\block\BlockTypeIds;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use meltdown\arena\MDArena;
use meltdown\utils\entity\PowerupEntity;
use meltdown\utils\Powerups;
use function array_filter;

class PowerupHandler
{
    /** @var array<int, list<PowerupEntity>> [int FloorY => int powerup count] */
    public array $alivePowerupCount;
    /** @var MDArena */
    private MDArena $arena;
    /** @var int How many powerups will be dropped on each floor */
    private int $powerupPerFloor;
    /** @var array<int, Vector3[]> [int FloorY => Vector3[]] */
    private array $blocksInPlay;
    /** @var array<int, int> [int FloorY => int block count] */
    private array $originalBlockCount;

    /**
     * @param MDArena $arena
     */
    public function __construct(MDArena $arena)
    {
        $this->arena = $arena;

        $this->powerupPerFloor = (int)round($this->getArena()->getArenaConfig()->getRadius($this->getArena()) / 7);
        if ($this->powerupPerFloor < 1) $this->powerupPerFloor = 1;

        foreach ($this->getArena()->getArenaConfig()->getAllFloors($this->getArena()) as $floor) {
            $this->blocksInPlay[$floor] = [];
            $this->alivePowerupCount[$floor] = [];
            $this->originalBlockCount[$floor] = 0;
        }
    }

    /**
     * @return MDArena
     */
    public function getArena(): MDArena
    {
        return $this->arena;
    }

    public function setBlocksInPlay(): void
    {
        $spawn = $this->getArena()->getArenaConfig()->getSpawn($this->getArena());
        $radius = $this->getArena()->getArenaConfig()->getRadius($this->getArena());
        foreach ($this->getArena()->getArenaConfig()->getAllFloors($this->getArena()) as $y) {
            $blockCountInThisFloor = 0;
            for ($x = $spawn->getX() - $radius; $x <= $spawn->getX() + $radius; ++$x) {
                for ($z = $spawn->getZ() - $radius; $z <= $spawn->getZ() + $radius; ++$z) {
                    $block = $spawn->getWorld()->getBlockAt($x, $y, $z);
                    if ($block->getTypeId() !== BlockTypeIds::AIR) {
                        $vector3 = $block->getPosition()->asVector3();
                        $this->blocksInPlay[$y][(string)$vector3] = $vector3;
                        $blockCountInThisFloor++;
                    }
                }
            }
            $this->originalBlockCount[$y] = $blockCountInThisFloor;
        }
    }

    public function dropPowerups(): void
    {
        foreach ($this->getArena()->getArenaConfig()->getAllFloors($this->getArena()) as $floor) {
            $this->alivePowerupCount[$floor] = array_filter($this->alivePowerupCount[$floor], fn($powerup) => !$powerup->isFlaggedForDespawn());

            $alivePowerupCountInThisFloor = count($this->alivePowerupCount[$floor]);
            if ($alivePowerupCountInThisFloor < $this->powerupPerFloor) {
                $diff = $this->powerupPerFloor - $alivePowerupCountInThisFloor;
                $this->dropPowerup($floor, $diff);
            }
        }
    }

    /**
     * @param int $floor
     * @param int $count
     */
    public function dropPowerup(int $floor, int $count): void
    {
        if ($this->originalBlockCount[$floor] === 0) return;
        $blocks = $this->blocksInPlay[$floor];
        $div = count($blocks) / $this->originalBlockCount[$floor];
        if ($div >= 0.15) {
            while ($count > 0) {
                $vec3 = $this->getAvailableBlockToDrop($floor);
                if ($vec3 !== null) {
                    $this->alivePowerupCount[$floor][] = Powerups::spawnRandomPowerup(Location::fromObject($vec3->up(), $this->getArena()->getWorld()), $this->getArena());
                }
                $count--;
            }
        }
    }

    public function getAvailableBlockToDrop(int $floor): ?Vector3
    {
        if (empty($blocks = $this->blocksInPlay[$floor])) {
            return null;
        }

        return $this->blocksInPlay[$floor][array_rand($blocks)];
    }

    /**
     * @param Vector3 $vector3
     */
    public function removeBlock(Vector3 $vector3): void
    {
        $floor = $vector3->getFloorY();
        unset($this->blocksInPlay[$floor][(string)$vector3]);
    }
}