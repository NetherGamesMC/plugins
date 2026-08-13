<?php

declare(strict_types=1);

namespace survivalgames\utils;

use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use RuntimeException;
use survivalgames\SGArena;

class SGArenaConfig extends ArenaConfig
{

    public function setSpawn(string $map, int $spawnId, Location $location): void
    {
        $path = 'arenas.' . $map . '.spawns.' . $spawnId . '.';
        $this->config->setNested($path . 'x', $location->getFloorX() + 0.5);
        $this->config->setNested($path . 'y', $location->getFloorY());
        $this->config->setNested($path . 'z', $location->getFloorZ() + 0.5);
        $this->config->setNested($path . 'yaw', $location->getYaw());
        $this->config->setNested($path . 'pitch', $location->getPitch());
        $this->config->save();
    }

    public function getSpawn(SGArena $arena, World $level, int $spawnId): Location
    {
        $path = 'arenas.' . $arena->getMapName() . '.spawns.' . $spawnId . '.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');
        $yaw = $this->config->getNested($path . 'yaw');
        $pitch = $this->config->getNested($path . 'pitch');

        if (is_null($x) || is_null($y) || is_null($z)) {
            throw new RuntimeException("Arena team spawns cannot be null");
        }

        return new Location($x, $y + 0.5, $z, $level, $yaw, $pitch);
    }

    public function setMidpoint(string $arena, Location $location): void
    {
        $this->config->setNested('arenas.' . $arena . '.midpoint.x', $location->getFloorX());
        $this->config->setNested('arenas.' . $arena . '.midpoint.y', $location->getFloorY());
        $this->config->setNested('arenas.' . $arena . '.midpoint.z', $location->getFloorZ());
        $this->config->save();
    }

    public function getMidpoint(SGArena $arena): Vector3
    {
        // The good thing is, this thing is cached.
        $path = 'arenas.' . $arena->getMapName() . '.midpoint.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');

        if (is_null($x) || is_null($y) || is_null($z)) {
            throw new RuntimeException("Arena midpoint cannot be null");
        }

        return new Vector3((int)$x, (int)$y, (int)$z);
    }

    public function setBorderSettings(string $arena, array $settings): void
    {
        $this->config->setNested('arenas.' . $arena . '.border-settings', $settings);
        $this->config->save();
    }

    public function getBorderSettings(SGArena $arena): array
    {
        return (array)$this->config->getNested('arenas.' . $arena->getMapName() . '.border-settings', [
            'state-1' => '25;180',
            'state-2' => '75;240',
            'state-3' => '100;180',
            'final-round' => '32;60;60'
        ]);
    }

    public function createArena(string $map): void
    {
        $configuration = [
            'spawns' => [],
            'midpoint' => [],
            'border-settings' => [
                // The state of the borders, these state can be manipulated to control the speed of your border
                // closing in to the middle. Use the formula v=distance/time 'distance;seconds' derived as m/s to find
                // the "decrease" of area, the final round is "size:time till the border will closing in:total time"
                // These things are in orderly manner, the names... are not accounted for if its not in orderly manner.
                'state-1' => '25;180',
                'state-2' => '75;240',
                'state-3' => '100;180',
                'final-round' => '32;60;60'
            ]
        ];

        $this->config->setNested('arenas.' . $map, $configuration);
        $this->config->save();
    }
}
