<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author driesboy
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\minigames\listener;

use libminigames\events\arena\ArenaCleanupEvent;
use libReplay\session\record\RecordManager;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\event\Listener;
use pocketmine\Server;

/**
 * Cleans up NetherGames-side resources bound to an arena when it is destroyed: replay recordings
 * and registered entities.
 */
final class EntityCleanupListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param ArenaCleanupEvent $event
     *
     * @priority NORMAL
     */
    public function onCleanup(ArenaCleanupEvent $event): void
    {
        $arena = $event->getArena();
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();

        $world = $worldManager->getWorldByName($arena->getMatchWorldName());
        if ($world === null) {
            return;
        }

        if (($recordManager = RecordManager::getInstance()) !== null) {
            $recordManager->stopRecording($world, $arena->isFinishing() || $arena->getStatus() === \libminigames\Arena::STATUS_FINISHING);
        }

        $this->plugin->getEntityManager()->removeEntities($world);
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}