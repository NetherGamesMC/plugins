<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars\utils;

use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;
use pocketmine\utils\TextFormat;
use skywars\SWArena;
use function round;

class SWArenaConfig extends ArenaConfig
{

    public function setTeamSpawn(string $map, int $teamId, Location $location): void
    {
        $path = 'arenas.' . $map . '.spawns.' . $teamId . '.';
        $this->config->setNested($path . 'x', round($location->getX() * 2) / 2);
        $this->config->setNested($path . 'y', round($location->getY() * 2) / 2);
        $this->config->setNested($path . 'z', round($location->getZ() * 2) / 2);
        $this->config->setNested($path . 'yaw', round($location->getYaw() / 45) * 45);
        $this->config->save();
    }

    public function getTeamSpawn(SWArena $arena, int $teamId): Location
    {
        $path = 'arenas.' . $arena->getMapName() . '.spawns.' . $teamId . '.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');
        $yaw = $this->config->getNested($path . 'yaw');

        if ($x === null || $y === null || $z === null || $yaw === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team spawn for team " . $teamId . " in map " . $arena->getMapName()
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        return new Location($x, $y + 0.5, $z, $arena->getWorld(), $yaw, 0);
    }

    public function setLuckyBlockSpawn(string $map, Location $location): void
    {
        $spawns = $this->config->getNested('arenas.' . $map . '.lucky-block-spawns', []);

        $spawns[] = [
            'x' => round($location->getX() * 2) / 2,
            'y' => round($location->getY() * 2) / 2,
            'z' => round($location->getZ() * 2) / 2
        ];

        $this->config->setNested('arenas.' . $map . '.lucky-block-spawns', $spawns);
        $this->config->save();
    }

    public function getLuckyBlockSpawns(SWArena $arena): array
    {
        return array_map(static function (array $coordinates) use ($arena) {
            return new Location(
                $coordinates['x'],
                $coordinates['y'],
                $coordinates['z'],
                $arena->getWorld(), 0, 0);
        }, $this->config->getNested('arenas.' . $arena->getMapName() . '.lucky-block-spawns'));
    }

    public function createArena(string $map): void
    {
        $configuration = [
            'spawns' => [],
            'lucky-block-spawns' => []
        ];
        $this->config->setNested('arenas.' . $map, $configuration);
        $this->config->save();
    }

    public function removeArena(string $map): void
    {
        $this->config->removeNested('arenas.' . $map);
        $this->config->save();
    }
}