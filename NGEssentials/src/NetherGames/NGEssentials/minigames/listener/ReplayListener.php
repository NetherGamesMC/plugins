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

use libminigames\Arena;
use libminigames\events\arena\ArenaStartEvent;
use libReplay\session\record\RecordManager;
use libReplay\session\record\Recording;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\event\Listener;

/**
 * Starts replay recordings for match arenas. Recordings are stopped (and the replay saved) by
 * {@see EntityCleanupListener} when the arena is cleaned up. Replay ids are exposed for consumers
 * such as {@see PersistenceListener}.
 */
final class ReplayListener implements Listener
{
    /** @var \WeakMap<Arena, int> arena => replay id */
    private static \WeakMap $replayIds;

    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param ArenaStartEvent $event
     *
     * @priority NORMAL
     */
    public function onStart(ArenaStartEvent $event): void
    {
        if (NGEssentials::isInDevelopmentMode()) {
            return;
        }

        $arena = $event->getArena();
        $recordManager = RecordManager::getInstance();
        if ($recordManager === null || !$arena->getPlugin()->getReplaySystemStatus()) {
            return;
        }

        $playerManager = $this->plugin->getPlayerManager();

        $recordManager->startRecording(
            $arena->getWorld(),
            [
                RecordManager::DATA_MAP_NAME => $arena->getMapName(),
                RecordManager::DATA_PLAYERS => $playerManager->getPlayerNames($arena->getAlivePlayers(), true),
                RecordManager::DATA_PRIVATE => $arena->isPrivateGame(),
                RecordManager::DATA_TOUCH_ONLY => $arena->isTouchOnly(),
            ],
            static function (?Recording $recording) use ($arena): void {
                if ($recording !== null) {
                    self::storeReplayId($arena, $recording->getReplayId());
                }
            }
        );
    }

    /**
     * Returns the replay id recorded for the given arena, if any.
     */
    public static function getReplayId(Arena $arena): ?int
    {
        if (!isset(self::$replayIds) || !isset(self::$replayIds[$arena])) {
            return null;
        }

        return self::$replayIds[$arena];
    }

    private static function storeReplayId(Arena $arena, int $replayId): void
    {
        self::$replayIds ??= new \WeakMap();
        self::$replayIds[$arena] = $replayId;
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}