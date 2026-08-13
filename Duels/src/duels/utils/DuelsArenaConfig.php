<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
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

namespace duels\utils;

use duels\DuelsArena;
use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;
use pocketmine\utils\TextFormat;
use function round;

class DuelsArenaConfig extends ArenaConfig
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

    public function getTeamSpawn(DuelsArena $arena, int $teamId): ?Location
    {
        $path = 'arenas.' . $arena->getMapName() . '.spawns.' . $teamId . '.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');
        $yaw = $this->config->getNested($path . 'yaw');

        if ($x === null || $y === null || $z === null || $yaw === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team spawn for team " . $teamId . " in map " . $arena->getMapDisplayName()
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        return new Location($x, $y + 0.5, $z, $arena->getWorld(), $yaw, 0);
    }

    public function createArena(string $map): void
    {
        $configuration = [
            'spawns' => [],
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