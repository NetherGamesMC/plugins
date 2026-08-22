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


namespace NetherGames\NGEssentials\minigames\streak;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use function count;

/**
 * MySQL-backed win-streak tracking, keyed per player xuid and game key.
 *
 * <p>This replaces the per-process in-memory streak implementation so streaks persist and
 * aggregate across the whole network. The underlying queries
 * (<code>streaks.increment</code>, <code>streaks.reset</code>, <code>streaks.get_all</code>,
 * <code>streaks.get_single</code>) are provided by NetherGames' database configuration.
 */
final class Streaks
{
    public const TYPE_WIN = 'win';

    private function __construct()
    {
    }

    /**
     * @param callable(Streak): void $onSelected
     * @param callable(mixed): void|null $onError
     */
    public static function increment(string $xuid, string $gameKey, callable $onSelected, ?callable $onError = null): void
    {
        MySQLCredentials::executeSelect(
            queryName: 'streaks.increment',
            args: [
                'xuid' => $xuid,
                'gameKey' => $gameKey,
            ],
            onSelect: static function (array $rows) use ($xuid, $gameKey, $onSelected): void {
                if (count($rows) !== 1) {
                    NGEssentials::getInstance()->getLogger()->warning('Requested increment of the statistics of ' . $xuid . ' for ' . $gameKey . ', but the request returned ' . count($rows) . ' results');

                    return;
                }

                $onSelected(Streak::fromSQL($rows[0]));
            },
            onError: $onError
        );
    }

    /**
     * @param callable(): void $onUpdated
     * @param callable(mixed): void|null $onError
     */
    public static function reset(string $xuid, string $gameKey, ?callable $onUpdated = null, ?callable $onError = null): void
    {
        MySQLCredentials::executeChange('streaks.reset', [
            'xuid' => $xuid,
            'gameKey' => $gameKey,
        ], $onUpdated, $onError);
    }

    /**
     * @param callable(Streak): void $onReceive
     * @param callable(mixed): void|null $onError
     */
    public static function getSingle(string $xuid, string $gameKey, callable $onReceive, ?callable $onError = null): void
    {
        MySQLCredentials::executeSelect(
            queryName: 'streaks.get_single',
            args: [
                'xuid' => $xuid,
                'gameKey' => $gameKey,
            ],
            onSelect: static function (array $rows) use ($onReceive): void {
                if (count($rows) === 1) {
                    $onReceive(Streak::fromSQL($rows[0]));

                    return;
                }

                $onReceive(Streak::fromSQL([
                    'xuid' => '',
                    'gameKey' => '',
                    'current' => 0,
                    'best' => 0,
                ]));
            },
            onError: $onError
        );
    }
}