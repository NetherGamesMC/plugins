<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace murdermystery\utils;

use libminigames\utils\ArenaConfig;
use murdermystery\gamemodes\MMArena;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use function array_key_last;
use function mt_rand;

class MMArenaConfig extends ArenaConfig
{

    public function setSpawn(string $map, Location $location, int $index): void
    {
        $path = 'arenas.' . $map . '.spawns.' . $index . '.';
        $this->config->setNested($path . 'x', $location->x);
        $this->config->setNested($path . 'y', $location->y);
        $this->config->setNested($path . 'z', $location->z);
        $this->config->setNested($path . 'yaw', $location->yaw);
        $this->config->setNested($path . 'pitch', $location->pitch);
        $this->config->save();
    }

    public function setResourceSpawn(string $map, Vector3 $vector3, int $index): void
    {
        $path = 'arenas.' . $map . '.resources.spawn.' . $index . '.';
        $this->config->setNested($path . 'x', $vector3->x);
        $this->config->setNested($path . 'y', $vector3->y);
        $this->config->setNested($path . 'z', $vector3->z);
        $this->config->save();
    }

    public function getRandomResourceSpawn(MMArena $arena): Vector3
    {
        $index = $this->getRandomResourceSpawnIndex($arena);

        $path = 'arenas.' . $arena->getMapName() . '.resources.spawn.' . $index . '.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');

        if ($x === null || $y === null || $z === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team spawn " . $index . " in map " . $arena->getMapDisplayName()
            );

            return $arena->getWorld()->getSpawnLocation();
        }

        return new Vector3($x, $y, $z);
    }

    private function getRandomResourceSpawnIndex(MMArena $arena): int
    {
        $spawns = $this->config->getNested('arenas.' . $arena->getMapName() . '.resources.spawn');

        return mt_rand(0, (int)array_key_last($spawns));
    }

    private function getRandomSpawnIndex(MMArena $arena): int
    {
        $spawns = $this->config->getNested('arenas.' . $arena->getMapName() . '.spawns', []);

        return mt_rand(0, (int)array_key_last($spawns));
    }

    public function getRandomSpawn(MMArena $arena): Location
    {
        $index = $this->getRandomSpawnIndex($arena);

        $path = 'arenas.' . $arena->getMapName() . '.spawns.' . $index . '.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');
        $yaw = $this->config->getNested($path . 'yaw');

        if ($x === null || $y === null || $z === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find spawn " . $index . " in map " . $arena->getMapDisplayName()
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        return new Location($x, $y + 0.5, $z, $arena->getWorld(), $yaw, 0);
    }

    public function createArena(string $map): void
    {
        $configuration = [
            'resources' => [
                'spawn' => []
            ],
            'spawns' => []
        ];
        $this->config->setNested('arenas.' . $map, $configuration);
        $this->config->save();
    }
}