<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\events\arena;

use pocketmine\player\Player;

/**
 * Immutable snapshot of a player's stats at the end of a match.
 */
final class PlayerMatchStats
{
    /**
     * @param array<string, int> $stats
     * @param array<string, string> $extra
     */
    public function __construct(
        private Player           $player,
        private bool             $winner,
        private array            $stats,
        private array            $extra
    )
    {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function isWinner(): bool
    {
        return $this->winner;
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    public function getStat(string $name, mixed $default = 0): mixed
    {
        return $this->stats[$name] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}