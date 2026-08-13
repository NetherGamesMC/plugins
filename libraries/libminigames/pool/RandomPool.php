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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libminigames\pool;

use InvalidArgumentException;

/**
 * This pool generates a random value using the built-in `array_rand` method
 * @template TValue
 * @implements Pool<TValue>
 */
class RandomPool implements Pool
{
    /**
     * @param array<array-key, TValue> $entries
     */
    public function __construct(protected array $entries)
    {
        // Sanity check to ensure that at least one entry is provided
        if (count($entries) === 0) {
            throw new InvalidArgumentException("Entries cannot be empty");
        }
    }

    /**
     * Returns a random value if the specified amount is one.
     * Otherwise, it returns an array of random values.
     *
     * @param int $amount
     * @return TValue|array<TValue>
     */
    public function pull(int $amount = 1): mixed
    {
        $keys = array_rand($this->entries, $amount);
        return match (true) {
            is_array($keys) => array_map(fn(int|string $key) => $this->entries[$key], $keys),
            default => $this->entries[$keys]
        };
    }
}