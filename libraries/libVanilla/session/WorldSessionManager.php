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

use libVanilla\listener\WorldSessionListener;
use libVanilla\network\types\DimensionType;
use libVanilla\network\types\WeatherType;
use pocketmine\event\EventPriority;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class WorldSessionManager
{
    use SingletonTrait;

    protected static WorldSessionListener $listener;
    protected static bool $registered = false;
    /**
     * A map of all world folder names to their respective WorldSession objects.
     *
     * @var array<string, WorldSession>
     */
    protected array $sessions = [];

    public static function register(PluginBase $plugin): void
    {
        if (self::$registered) {
            return;
        }
        $pluginManager = $plugin->getServer()->getPluginManager();
        // We register this event here as a way to keep the session creation/deletion logic in one place.
        $pluginManager->registerEvent(
            event: WorldUnloadEvent::class,
            handler: function (WorldUnloadEvent $event): void {
                self::getInstance()->delete($event->getWorld());
            },
            priority: EventPriority::LOWEST,
            plugin: $plugin
        );

        // Register the session listener
        // This listener is primarily used for synchronizing changes when a player joins, teleports to a new world, etc.
        $plugin->getServer()->getPluginManager()->registerEvents(
            listener: new WorldSessionListener(self::getInstance()),
            plugin: $plugin
        );
        self::$registered = true;
    }

    /**
     * Deletes the world session from the manager.
     *
     * @param World $world
     * @return void
     */
    public function delete(World $world): void
    {
        if (isset($this->sessions[$world->getFolderName()])) {
            unset($this->sessions[$world->getFolderName()]);
        }
    }

    /**
     * Retrieves (or creates) the world session for the world.
     *
     * @param World $world
     * @return WorldSession
     */
    public function get(World $world): WorldSession
    {
        return $this->sessions[$world->getFolderName()] ??= WorldSession::create($world, DimensionType::OVERWORLD, WeatherType::CLEAR());
    }
}