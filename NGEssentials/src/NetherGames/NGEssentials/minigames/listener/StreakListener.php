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
use libminigames\events\arena\PlayerMatchStats;
use libminigames\events\player\PlayerQuitEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\minigames\streak\Streaks;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use function in_array;

/**
 * Tracks win streaks for players using MySQL-backed counters, so they persist and aggregate
 * network-wide.
 */
final class StreakListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param ArenaEndEvent $event
     *
     * @priority MONITOR
     */
    public function onEnd(ArenaEndEvent $event): void
    {
        $arena = $event->getArena();
        $key = $arena->getStreaksKey();
        if ($key === null) {
            return;
        }

        foreach ($event->getMatchResult()->getPlayerStats() as $playerStats) {
            $this->update($playerStats, $key);
        }
    }

    private function update(PlayerMatchStats $playerStats, string $gameKey): void
    {
        $player = $playerStats->getPlayer();
        $xuid = $player->getXuid();

        if ($playerStats->isWinner()) {
            Streaks::increment(
                xuid: $xuid,
                gameKey: $gameKey,
                onSelected: static function ($streak) use ($player): void {
                    if (!$player->isConnected()) {
                        return;
                    }

                    if ($streak->isBestChanged()) {
                        $player->sendMessage('§l§6NEW BEST WIN STREAK: §r§b' . $streak->getCurrent());
                    } else {
                        $player->sendMessage('§6Your win streak is now §b' . $streak->getCurrent() . '§6 (best streak: §b' . $streak->getBest() . '§6)');
                    }
                },
                onError: static function () use ($player): void {
                    if ($player->isConnected()) {
                        $player->sendMessage(TextFormat::RED . 'An error occurred while updating your streak.');
                    }
                }
            );

            return;
        }

        Streaks::reset(
            xuid: $xuid,
            gameKey: $gameKey,
            onUpdated: static function () use ($player): void {
                if ($player->isConnected()) {
                    $player->sendMessage(TextFormat::RED . "You lost your streak!");
                }
            },
            onError: static function () use ($player): void {
                if ($player->isConnected()) {
                    $player->sendMessage(TextFormat::RED . 'An error occurred while updating your streak.');
                }
            }
        );
    }

    /**
     * A player leaving a running match forfeits their win streak; a match is only considered won
     * once it actually ends (see {@see StreakListener::onEnd()}).
     *
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $arena = $event->getArena();
        $key = $arena->getStreaksKey();
        if ($key === null) {
            return;
        }
        if (!$arena->isRunning()) {
            return;
        }
        if ($arena->isPartyGame()) {
            return;
        }
        if ($event->getReason() === PlayerQuitEvent::DISCONNECT_KICK) {
            return;
        }

        $player = $event->getPlayer();
        if (!in_array($player, $arena->getPlayers(false), true)) {
            return;
        }

        Streaks::reset(
            xuid: $player->getXuid(),
            gameKey: $key
        );
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}