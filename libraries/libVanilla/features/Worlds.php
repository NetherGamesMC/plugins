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

namespace libVanilla\features;

use libVanilla\network\types\DimensionType;
use libVanilla\network\types\WeatherType;
use libVanilla\session\PlayerSessionManager;
use libVanilla\session\WorldSessionManager;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class Worlds extends Feature
{

    /** The amount of ticks to wait before teleporting the player */
    public const DEFAULT_DIMENSION_TELEPORT_DELAY = 10;
    /** The default weather strength to use in {@link sendWeather()} */
    public const DEFAULT_WEATHER_STRENGTH = 10000;

    protected PluginBase $plugin;

    protected function setup(PluginBase $plugin): void
    {
        WorldSessionManager::register($plugin);
        $this->plugin = $plugin;
    }

    /**
     * This method updates the player's weather client-side.
     *
     * @param Player $player
     * @param WeatherType $type
     * @param int $strength
     * @return void
     */
    public function sendWeather(Player $player, WeatherType $type, int $strength = self::DEFAULT_WEATHER_STRENGTH): void
    {
        foreach ($type->encode() as $type) {
            $player->getNetworkSession()->sendDataPacket(LevelEventPacket::create(
                $type, $strength, null
            ));
        }
    }

    /**
     * This method sets the player's current dimension, both client-side and in their session, and teleports them to the new dimension.
     *
     * @param Player $player
     * @param DimensionType $type
     * @param Position|null $position
     * @param bool $respawn
     * @param int $delay
     * @return void
     */
    public function setDimension(Player $player, DimensionType $type, ?Position $position = null, bool $respawn = false, int $delay = self::DEFAULT_DIMENSION_TELEPORT_DELAY): void
    {
        $this->verifyRegistration();
        $playerSession = PlayerSessionManager::getInstance()->get($player);
        if ($playerSession->getDimensionType() === $type) {
            return;
        }

        $position = $position ?? $player->getPosition();
        $player->getNetworkSession()->sendDataPacket(ChangeDimensionPacket::create(
            $type->getId(),
            $position->asVector3(),
            $respawn,
            null
        ));

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($player, $playerSession, $position, $type): void {
            $player->teleport($position);
            $player->getNetworkSession()->sendDataPacket(PlayStatusPacket::create(
                status: PlayStatusPacket::PLAYER_SPAWN
            ));
            $player->getNetworkSession()->sendDataPacket(PlayerFogPacket::create(
                fogLayers: [$type->getFogType()]
            ));
            $playerSession->setDimensionType($type);
        }), $delay);
    }

}