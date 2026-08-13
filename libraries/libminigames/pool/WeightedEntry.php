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

/**
 * A general weighted entry class used to implement any type of weighted pool system.
 * These entries are used to couple the value and its weight in the pool
 *
 * @template TValue
 * @extends Entry<TValue>
 */
class WeightedEntry extends Entry
{

    /**
     * @param TValue $value
     */
    public function __construct(protected $value, protected int|float $weight)
    {
    }

    /**
     * @return TValue
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getWeight(): int|float
    {
        return $this->weight;
    }

    /**
     * @return TValue
     */
    public function generate(): mixed
    {
        return $this->value;
    }
}