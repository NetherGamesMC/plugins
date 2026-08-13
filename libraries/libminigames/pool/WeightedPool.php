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
 * This pool implementation uses the Vose Alias Method to generate a table of probabilities as a way to randomly select an item.
 *
 * The Vose Alias Method is a method of generating discrete random variables with a given probability distribution.
 * When initialized, the entry weights are divided in a way that each column of the table sums to 1.
 *
 * The best way to summarize this method is as follows:
 *   1. A 2 row table is created with n columns, where n is the number of items in the pool.
 *   2. The entries are divided and arranged in a way that each column sums to 1.
 *   3. When pulling an item, a random number between 0 - (n - 1) is generated. This determines which column to pull from.
 *   4. After that, a random number between 0 - 1 is generated. This random number is then compared against the probability using the probability table.
 *   5. If the random number is less than the probability, the item is pulled from the column. Otherwise, the alias table is used to pull the other item.
 *
 * The best resources for learning more about this method are:
 * - https://en.wikipedia.org/wiki/Alias_method
 * - https://www.keithschwarz.com/darts-dice-coins/
 * - https://www.youtube.com/watch?v=dQw4w9WgXcQ
 *
 * @phpstan-type Worklist array{0: int|float, 1: int|float}
 * @template TValue
 * @implements Pool<TValue>
 */
class WeightedPool implements Pool
{
    /** This is the scale that weighted pool operates from */
    public const TOTAL_WEIGHT_SCALE = 100;
    /**
     * The probability table is used when pulling an item from the pool.
     * All values in this table are between 0 and 100.
     *
     * @var array<int, int|float>
     */
    protected array $probabilityTable = [];

    /**
     * The alias table is a mapping of the original entry index => to an alias entry index.
     *
     * The alias entry is used as a way to provide an alternative if the probability is not met for the original entry.
     *
     * @var array<int, int>
     */
    protected array $aliasTable = [];

    /**
     * @param array<WeightedEntry<TValue>> $entries
     */
    public function __construct(protected array $entries)
    {
        // Sanity check to ensure that at least one entry is provided
        if (count($entries) === 0) {
            throw new InvalidArgumentException("Entries cannot be empty");
        }

        $weightTotal = array_reduce($entries, static fn(int|float $carry, WeightedEntry $entry) => $carry + $entry->getWeight(), 0);

        // round is used as PHP can have issues with floating point precision.
        if ((int)round($weightTotal, 1) !== self::TOTAL_WEIGHT_SCALE) {
            throw new InvalidArgumentException("Entry weights must add up to " . self::TOTAL_WEIGHT_SCALE . ". Current weight total: $weightTotal");
        }
        // Destroy any string keys that may have been established in the parameter
        $this->entries = array_values($entries);

        $this->initialize();
    }

    /**
     * The initialization method is O(n), where n is the number of entries in the pool.
     * After initialization, any subsequent calls to WeightedPool->pull() are O(1)
     *
     */
    private function initialize(): void
    {
        [$smallWorklist, $largeWorklist] = $this->initializeWorklists();

        while (count($smallWorklist) > 0 && count($largeWorklist) > 0) {
            /**
             * @var int $smallKey
             * @var int $smallWeight
             */
            [$smallKey, $smallWeight] = array_shift($smallWorklist);
            /**
             * @var int $largeKey
             * @var int $largeWeight
             */
            [$largeKey, $largeWeight] = array_shift($largeWorklist);

            $this->probabilityTable[$smallKey] = $smallWeight;
            $this->aliasTable[$smallKey] = $largeKey;

            $largeWeight = ($largeWeight + $smallWeight) - self::TOTAL_WEIGHT_SCALE;
            if ($largeWeight < self::TOTAL_WEIGHT_SCALE) {
                $smallWorklist[] = [$largeKey, $largeWeight];
            } else {
                $largeWorklist[] = [$largeKey, $largeWeight];
            }
        }

        // this callable will empty out whatever is left in the worklists
        $emptyWorklistCallable = function (array $worklist) {
            while (count($worklist) > 0) {
                /** @var int $key */
                [$key, $_] = array_shift($worklist);
                /**
                 * We can assume the probability for the leftover items is 100
                 */
                $this->probabilityTable[$key] = self::TOTAL_WEIGHT_SCALE;
            }
        };


        $emptyWorklistCallable($largeWorklist);
        $emptyWorklistCallable($smallWorklist);
    }

    /**
     * This method does two things:
     *   1. It scales every entry's weight by the total number of entries in the pool. This ensures that each column will have a weight of 1.0 after initialization.
     *   2. It splits the entries into two worklists:
     *      - Large worklist: The list of entries with a scaled weight of 1.0 or greater
     *      - Small worklist: The list of entries with a scaled weight less than 1.0
     *
     * Worklists are incredibly useful as they allow us to transform the algorithm
     * from O(n log n) to O(n)
     *
     * @return array{array<Worklist>, array<Worklist>}
     */
    private function initializeWorklists(): array
    {
        $smallWorklist = [];
        $largeWorklist = [];
        /** To scale the weight to a proper size that fits, we use the entry count */
        $scale = count($this->entries);
        foreach ($this->entries as $key => $entry) {
            $scaledWeight = $entry->getWeight() * $scale;
            if ($scaledWeight < self::TOTAL_WEIGHT_SCALE) {
                $smallWorklist[] = [$key, $scaledWeight];
            } else {
                $largeWorklist[] = [$key, $scaledWeight];
            }
        }
        return [$smallWorklist, $largeWorklist];
    }

    /**
     * Generates the specified number of weighable entries
     *
     * @param int $amount
     * @return TValue|array<TValue>
     */
    public function pull(int $amount = 1): mixed
    {
        return match ($amount) {
            // If zero is specified, return an empty array
            0 => [],
            1 => $this->select()->generate(),
            default => array_map(fn() => $this->select()->generate(), range(1, $amount))
        };
    }

    /**
     * Selects an entry from the list of entries based on random values
     * @return WeightedEntry<TValue>
     */
    private function select(): WeightedEntry
    {
        $column = array_rand($this->probabilityTable);
        return $this->probabilityTable[$column] >= mt_rand(1, self::TOTAL_WEIGHT_SCALE) ? $this->entries[$column] : $this->entries[$this->aliasTable[$column]];
    }
}