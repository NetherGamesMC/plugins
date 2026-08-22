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

use function array_key_exists;

/**
 * A single win-streak snapshot for a player+game pair, returned by {@see Streaks}.
 */
final class Streak
{
    public function __construct(
        private string $xuid,
        private string $gameKey,
        private int    $current,
        private int    $best,
        private bool   $bestChanged = false
    )
    {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromSQL(array $row): Streak
    {
        return new Streak(
            (string)$row['xuid'],
            (string)$row['gameKey'],
            (int)$row['current'],
            (int)$row['best'],
            array_key_exists('bestChanged', $row) && (bool)$row['bestChanged']
        );
    }

    public function getXuid(): string
    {
        return $this->xuid;
    }

    public function getGameKey(): string
    {
        return $this->gameKey;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    public function getBest(): int
    {
        return $this->best;
    }

    public function isBestChanged(): bool
    {
        return $this->bestChanged;
    }
}