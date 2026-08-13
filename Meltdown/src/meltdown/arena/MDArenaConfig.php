<?php

namespace meltdown\arena;

use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;

class MDArenaConfig extends ArenaConfig
{

    /**
     * @param string $mapName
     */
    public function createArena(string $mapName): void
    {
        $configuration = [
            "spawn" => [
                "x" => 0,
                "y" => 80,
                "z" => 0
            ],
            "radius" => 30,
            "floors" => [
                "min" => 0,
                "max" => 128,
                "all" => [
                    0,
                    128
                ]
            ]
        ];
        $this->config->setNested("arenas." . $mapName, $configuration);
        $this->config->save();
    }

    /**
     * @param string $mapName
     * @param Location $location
     */
    public function setSpawn(string $mapName, Location $location): void
    {
        $this->config->setNested("arenas.$mapName.spawn.x", $location->getX());
        $this->config->setNested("arenas.$mapName.spawn.y", $location->getY());
        $this->config->setNested("arenas.$mapName.spawn.z", $location->getZ());
        $this->config->setNested("arenas.$mapName.spawn.yaw", $location->getYaw());
        $this->config->save();
    }

    /**
     * @param MDArena $arena
     * @return Location
     */
    public function getSpawn(MDArena $arena): Location
    {
        $mapName = $arena->getMapName();

        $path = "arenas.$mapName.spawn.";

        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');
        $yaw = $this->config->getNested($path . 'yaw');

        return new Location($x, $y + 0.5, $z, $arena->getWorld(), $yaw, 0);
    }

    /**
     * @param string $mapName
     * @param int $radius
     */
    public function setRadius(string $mapName, int $radius): void
    {
        $this->config->setNested("arenas.$mapName.radius", $radius);
        $this->config->save();
    }

    /**
     * @param MDArena $arena
     * @return int
     */
    public function getRadius(MDArena $arena): int
    {
        $map = $arena->getMapName();
        return $this->config->getNested("arenas.$map.radius");
    }

    /**
     * @param string $mapName
     * @param int[] $floors
     */
    public function setFloors(string $mapName, array $floors): void
    {
        $this->config->setNested("arenas.$mapName.floors.min", min($floors));
        $this->config->setNested("arenas.$mapName.floors.max", max($floors));
        $this->config->setNested("arenas.$mapName.floors.all", $floors);
        $this->config->save();
    }

    /**
     * @param MDArena $arena
     * @return int
     */
    public function getMinFloor(MDArena $arena): int
    {
        $map = $arena->getMapName();
        return $this->config->getNested("arenas.$map.floors.min");
    }

    /**
     * @param MDArena $arena
     * @return int
     */
    public function getMaxFloor(MDArena $arena): int
    {
        $map = $arena->getMapName();
        return $this->config->getNested("arenas.$map.floors.max");
    }

    /**
     * @param MDArena $arena
     * @return int[]
     */
    public function getAllFloors(MDArena $arena): array
    {
        $map = $arena->getMapName();
        return $this->config->getNested("arenas.$map.floors.all");
    }

}