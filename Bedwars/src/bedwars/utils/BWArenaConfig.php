<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars\utils;

use bedwars\BWArena;
use libminigames\utils\ArenaConfig;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function round;

/**
 * @phpstan-type VectorData array{x: int|float, y: int|float, z: int|float}
 * @phpstan-type LocationData array{x: int|float, y: int|float, z: int|float, yaw: int|float}
 */
class BWArenaConfig extends ArenaConfig
{

    public function getBorderLimitConfig(string $map): ?AxisAlignedBB
    {
        /** @var array{0: string, 1: string}|null $pointData */
        $pointData = $this->config->getNested("arenas.$map.border");
        if ($pointData === null) {
            return $this->generateBorderConfig($map);
        }
        [$minPoint, $maxPoint] = $pointData;
        [$x1, $y1, $z1] = explode(":", $minPoint);
        [$x2, $y2, $z2] = explode(":", $maxPoint);
        return new AxisAlignedBB(
            min((int)$x1, (int)$x2),
            min((int)$y1, (int)$y2),
            min((int)$z1, (int)$z2),
            max((int)$x1, (int)$x2),
            max((int)$y1, (int)$y2),
            max((int)$z1, (int)$z2)
        );
    }

    public function generateBorderConfig(string $map, int $expansion = 30): ?AxisAlignedBB
    {
        /** @var array<VectorData|array{spawn: VectorData}> $teams */
        $teams = $this->config->getNested("arenas.$map.teams", []);
        /** @var array<VectorData|array{spawn: VectorData}> $emeraldGenerators */
        $emeraldGenerators = $this->config->getNested("arenas.$map.generators.emerald", []);
        /** @var array<VectorData|array{spawn: VectorData}> $diamondGenerators */
        $diamondGenerators = $this->config->getNested("arenas.$map.generators.diamond", []);
        $spawns = [...$teams, ...$emeraldGenerators, ...$diamondGenerators];

        if (count($spawns) === 0) {
            return null;
        }

        /** @var array{0: int|float, 1: int|float, 2: int|float}|null $minValues */
        $minValues = null;
        /** @var array{0: int|float, 1: int|float, 2: int|float}|null $maxValues */
        $maxValues = null;

        foreach ($spawns as $spawn) {
            /** @var VectorData $vectorData */
            $vectorData = $spawn["spawn"] ?? $spawn;
            ["x" => $rawX, "y" => $rawY, "z" => $rawZ] = $vectorData;
            $spawnPos = new Vector3((int)$rawX, (int)$rawY, (int)$rawZ);
            if ($minValues === null && $maxValues === null) {
                $minValues = $maxValues = [$spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ()];
                continue;
            }

            assert($minValues !== null && $maxValues !== null);
            [$minX, $minY, $minZ] = $minValues;
            [$maxX, $maxY, $maxZ] = $maxValues;

            $minValues = [min($minX, $spawnPos->getX()), min($minY, $spawnPos->getY()), min($minZ, $spawnPos->getZ())];
            $maxValues = [max($maxX, $spawnPos->getX()), max($maxY, $spawnPos->getY()), max($maxZ, $spawnPos->getZ())];
        }

        [$minX, $minY, $minZ] = $minValues;
        [$maxX, $maxY, $maxZ] = $maxValues;
        $bb = (new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ))->expand($expansion, $expansion, $expansion);
        $this->config->setNested(
            key: "arenas.$map.border",
            value: [
                "$bb->minX:$bb->minY:$bb->minZ",
                "$bb->maxX:$bb->maxY:$bb->maxZ"
            ]
        );
        $this->config->save();
        return $bb;
    }

    public function setTeamSpawn(string $map, int $teamId, Location $location): void
    {
        $this->config->setNested(
            key: "arenas.$map.teams.$teamId.spawn",
            value: [
                "x" => round($location->getX() * 2) / 2,
                "y" => round($location->getY() * 2) / 2,
                "z" => round($location->getZ() * 2) / 2,
                "yaw" => round($location->getYaw() / 45) * 45
            ]
        );
        $this->config->save();
    }

    public function getTeamSpawn(BWArena $arena, int $teamId): Location
    {
        /** @var LocationData|null $locationData */
        $locationData = $this->config->getNested("arenas.{$arena->getMapName()}.teams.$teamId.spawn");
        if ($locationData === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team spawn for team $teamId in map {$arena->getMapDisplayName()}"
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        ["x" => $x, "y" => $y, "z" => $z, "yaw" => $yaw] = $locationData;
        return new Location($x, $y + 0.5, $z, $arena->getWorld(), $yaw, 0);
    }

    public function getTeamGenerator(BWArena $arena, int $teamId): Vector3
    {
        /** @var VectorData|null $vectorData */
        $vectorData = $this->config->getNested("arenas.{$arena->getMapName()}.teams.$teamId.generator");
        if ($vectorData === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team generator for team $teamId in map {$arena->getMapDisplayName()}"
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        ["x" => $x, "y" => $y, "z" => $z] = $vectorData;
        return new Vector3($x, $y + 1, $z);
    }

    public function createArena(string $map): void
    {
        $this->config->setNested(
            key: "arenas.$map",
            value: [
                "teams" => [],
                "generators" => [
                    "diamond" => [],
                    "emerald" => [],
                ],
            ]
        );
        $this->config->save();
    }

    public function removeArena(string $map): void
    {
        $this->config->removeNested("arenas.$map");
        $this->config->save();
    }

    public function setTeamGenerator(string $map, int $teamId, Vector3 $pos): void
    {
        $this->config->setNested(
            key: "arenas.$map.teams.$teamId.generator",
            value: [
                "x" => round($pos->getX() * 2) / 2,
                "y" => round($pos->getY() * 2) / 2,
                "z" => round($pos->getZ() * 2) / 2,
            ]
        );
        $this->config->save();
    }

    public function getTeamShop(BWArena $arena, int $teamId, World $world): Location
    {
        /** @var LocationData|null $locationData */
        $locationData = $this->config->getNested("arenas.{$arena->getMapName()}.teams.$teamId.shop");

        if ($locationData === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team shop for team $teamId in map {$arena->getMapDisplayName()}"
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }
        ["x" => $x, "y" => $y, "z" => $z, "yaw" => $yaw] = $locationData;
        return new Location($x, $y, $z, $world, $yaw, 0);
    }

    public function setTeamShop(string $map, int $teamId, Location $location): void
    {
        $this->config->setNested(
            key: "arenas.$map.teams.$teamId.shop",
            value: [
                "x" => round($location->getX() * 2) / 2,
                "y" => round($location->getY() * 2) / 2,
                "z" => round($location->getZ() * 2) / 2,
                "yaw" => round($location->getYaw() / 45) * 45,
            ]
        );
        $this->config->save();
    }

    public function getTeamUpgrader(BWArena $arena, int $teamId, World $world): Location
    {
        /** @var LocationData|null $locationData */
        $locationData = $this->config->getNested("arenas.{$arena->getMapName()}.teams.$teamId.upgrader");

        if ($locationData === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find team upgrader for team $teamId in map {$arena->getMapDisplayName()}"
            );

            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        ["x" => $x, "y" => $y, "z" => $z, "yaw" => $yaw] = $locationData;
        return new Location($x, $y, $z, $world, $yaw, 0);
    }

    public function setTeamUpgrader(string $map, int $teamId, Location $location): void
    {
        $this->config->setNested(
            key: "arenas.$map.teams.$teamId.upgrader",
            value: [
                "x" => round($location->getX() * 2) / 2,
                "y" => round($location->getY() * 2) / 2,
                "z" => round($location->getZ() * 2) / 2,
                "yaw" => round($location->getYaw() / 45) * 45,
            ]
        );
        $this->config->save();
    }

    public function setGenerator(string $map, string $type, int $id, Vector3 $pos): void
    {
        $this->config->setNested(
            key: "arenas.$map.generators.$type.$id",
            value: [
                "x" => $pos->getFloorX(),
                "y" => $pos->getFloorY(),
                "z" => $pos->getFloorZ(),
            ]
        );
        $this->config->save();
    }

    public function getGenerator(BWArena $arena, string $type, int $id): Vector3
    {
        /** @var VectorData|null $vectorData */
        $vectorData = $this->config->getNested("arenas.{$arena->getMapName()}.generators.$type.$id");

        if ($vectorData === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find generator $id of type $type in map {$arena->getMapDisplayName()}"
            );
            $world = $arena->getWorld();
            return Location::fromObject($world->getSpawnLocation(), $world);
        }

        ["x" => $x, "y" => $y, "z" => $z] = $vectorData;
        return new Vector3($x + 0.5, $y, $z + 0.5);
    }

    /**
     * @return array<int, VectorData>
     */
    public function getGenerators(BWArena $arena, ?string $type = null): array
    {
        /** @var array<int, VectorData>|null $generators */
        $generators = $this->config->getNested("arenas.{$arena->getMapName()}.generators.$type");
        if ($generators === null) {
            $arena->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Could not find generators of type $type for map {$arena->getMapDisplayName()}"
            );
            return [];
        }
        return $generators;
    }
}