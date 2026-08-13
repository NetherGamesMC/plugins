<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges\utils;

use bridges\BridgeArena;
use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function round;

class BridgeArenaConfig extends ArenaConfig
{

    public function setTeamSpawn(string $map, int $teamId, Location $location): void
    {
        $path = 'arenas.' . $map . '.teams.' . $teamId . '.spawn.';
        $this->config->setNested($path . 'x', round($location->getX() * 2) / 2);
        $this->config->setNested($path . 'y', round($location->getY() * 2) / 2);
        $this->config->setNested($path . 'z', round($location->getZ() * 2) / 2);
        $this->config->setNested($path . 'yaw', round($location->getYaw() / 45) * 45);
        $this->config->save();
    }

    public function getTeamSpawn(BridgeArena $arena, World $world, int $teamId): Location
    {
        $path = 'arenas.' . $arena->getMapName() . '.teams.' . $teamId . '.spawn.';
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

        return new Location($x, $y + 0.5, $z, $world, $yaw, 0);
    }

    public function getTeamPoint(BridgeArena $arena, int $teamId): Vector3
    {
        $path = 'arenas.' . $arena->getMapName() . '.teams.' . $teamId . '.point.';
        $x = $this->config->getNested($path . 'x');
        $y = $this->config->getNested($path . 'y');
        $z = $this->config->getNested($path . 'z');

        if ($x === null || $y === null || $z === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team point for team " . $teamId . " in map " . $arena->getMapName()
            );
            return $arena->getWorld()->getSpawnLocation();
        }

        return new Vector3($x + 0.5, $y, $z + 0.5);
    }

    public function createArena(string $map): void
    {
        $configuration = [
            'teams' => [],
        ];
        $this->config->setNested('arenas.' . $map, $configuration);
        $this->config->save();
    }

    public function removeArena(string $map): void
    {
        $this->config->removeNested('arenas.' . $map);
        $this->config->save();
    }

    public function setTeamPoint(string $map, int $teamId, Vector3 $pos): void
    {
        $path = 'arenas.' . $map . '.teams.' . $teamId . '.point.';
        $this->config->setNested($path . 'x', $pos->getFloorX());
        $this->config->setNested($path . 'y', $pos->getFloorY());
        $this->config->setNested($path . 'z', $pos->getFloorZ());
        $this->config->save();
    }
}
