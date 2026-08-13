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

namespace libminigames\loot;

use libminigames\loot\rolls\ConstantRoll;
use libminigames\loot\rolls\Roll;
use libminigames\pool\WeightedEntry;
use libminigames\pool\WeightedPool;
use pocketmine\item\Item;

/**
 * Loot pools can consist of:
 * 1. Nested lootable instances
 * 2. An array of `Item`s
 * 3. A singular `Item`
 *
 * @phpstan-type AcceptedPoolTypes Lootable|Item|array<Item>
 * @extends WeightedPool<AcceptedPoolTypes>
 */
class LootPool extends WeightedPool implements Lootable
{
    protected Roll $roll;

    /**
     * @param array<WeightedEntry<AcceptedPoolTypes>> $entries
     * @param Roll|null $roll - If not specified, $roll will default to `ConstantRoll::one()`
     */
    public function __construct(array $entries, ?Roll $roll = null)
    {
        parent::__construct($entries);
        // If specified, the roll is the value passed. Otherwise, it defaults to `ConstantRoll::one()`
        $this->roll = $roll ?? ConstantRoll::one();
    }

    /**
     * Creates a {@link WeightedEntry} instance with a pool inside it
     * @param array<WeightedEntry<AcceptedPoolTypes>> $entries
     * @return WeightedEntry<LootPool>
     */
    public static function createEntry(array $entries, int|float $weight, ?Roll $roll = null): WeightedEntry
    {
        return new WeightedEntry(
            value: new self(
                entries: $entries,
                roll: $roll
            ),
            weight: $weight
        );
    }

    /**
     * @return array<Item>
     */
    public function roll(): array
    {
        /** @var AcceptedPoolTypes|array<AcceptedPoolTypes> $value */
        $value = $this->pull($this->roll->generate());
        return match (true) {
            // If the value pulled is an array, reduce that as far as possible
            is_array($value) => array_reduce(
                array: $value,
                callback: fn(array $result, Lootable|Item|array $current) => [...$result, ...$this->flatten($current)],
                initial: []
            ),
            default => $this->flatten($value)
        };
    }

    /**
     * @param AcceptedPoolTypes $value
     * @return array<Item>
     */
    private function flatten(Lootable|Item|array $value): array
    {
        return match (true) {
            $value instanceof Lootable => $value->roll(),
            is_array($value) => $value,
            default => [$value]
        };
    }
}