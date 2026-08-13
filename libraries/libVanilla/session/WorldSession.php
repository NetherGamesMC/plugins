<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\session;

use libVanilla\network\types\DimensionType;
use libVanilla\network\types\WeatherType;
use libVanilla\VanillaPlugin;
use pocketmine\player\Player;
use pocketmine\world\World;

class WorldSession
{
    public function __construct(
        protected World         $world,
        protected DimensionType $dimension,
        protected WeatherType   $currentWeather,
        protected int           $currentWeatherStrength = 0
    )
    {
    }

    public static function create(World $world, DimensionType $dimension, WeatherType $currentWeather, int $currentWeatherStrength = 0): self
    {
        return new WorldSession($world, $dimension, $currentWeather, $currentWeatherStrength);
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function getCurrentWeather(): WeatherType
    {
        return $this->currentWeather;
    }

    public function setCurrentWeather(WeatherType $weather, ?int $strength = null): void
    {
        $this->currentWeather = $weather;
        // We don't call `setWeatherStrength` here as to prevent `syncAll()` from being called twice.
        if ($strength !== null) {
            $this->currentWeatherStrength = $strength;
        }
        $this->syncAll();
    }

    public function getWeatherStrength(): int
    {
        return $this->currentWeatherStrength;
    }

    public function setWeatherStrength(int $strength): void
    {
        $this->currentWeatherStrength = $strength;
        $this->syncAll();
    }

    public function getDimension(): DimensionType
    {
        return $this->dimension;
    }

    /**
     * This method synchronizes changes for all players in the world.
     *
     * @return void
     */
    public function syncAll(): void
    {
        foreach ($this->world->getPlayers() as $player) {
            $this->sync($player);
        }
    }

    /**
     * This method will synchronize any world changes to the client.
     *
     * @param Player $player
     * @return void
     */
    public function sync(Player $player): void
    {
        VanillaPlugin::WORLDS()->sendWeather($player, $this->currentWeather, $this->currentWeatherStrength);
        VanillaPlugin::WORLDS()->setDimension($player, $this->dimension);
    }

}