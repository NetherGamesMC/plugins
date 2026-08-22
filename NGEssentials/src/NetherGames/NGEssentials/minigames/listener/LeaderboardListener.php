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

use libminigames\events\arena\ArenaEndEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use function arsort;
use function count;

/**
 * Broadcasts a per-match leaderboard (podium) at the end of an arena.
 */
final class LeaderboardListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param ArenaEndEvent $event
     *
     * @priority NORMAL
     */
    public function onEnd(ArenaEndEvent $event): void
    {
        $arena = $event->getArena();
        $stats = [];

        foreach ($event->getMatchResult()->getPlayerStats() as $playerStats) {
            $kills = $playerStats->getStat('kills', 0);
            if ($kills !== 0) {
                $stats[$playerStats->getPlayer()->getDisplayName()] = $kills;
            }
        }

        if (count($stats) === 0) {
            return;
        }

        arsort($stats);

        $arena->broadcastMessage('§e§l----------------------------', true);
        $arena->broadcastMessage('§6§lTop Kills', true);

        $podium = array_slice($stats, 0, 3, true);
        $place = 0;
        foreach ($podium as $playerName => $value) {
            $place++;

            $icon = match ($place) {
                1 => CustomIcon::TROPHY_GOLD,
                2 => CustomIcon::TROPHY_SILVER,
                default => CustomIcon::TROPHY_BRONZE,
            };

            $arena->broadcastMessage($icon . '§r§7- §b' . $playerName . ' - §e' . $value, true);
        }

        $arena->broadcastMessage('§e§l----------------------------', true);
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}