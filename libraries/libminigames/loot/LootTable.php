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

use libminigames\loot\rolls\Roll;
use libminigames\pool\WeightedEntry;
use pocketmine\item\Item;
use function array_reduce;

/**
 * @phpstan-type AcceptedPoolTypes Lootable|Item|array<Item>
 */
class LootTable implements Lootable
{
    /**
     * @param array<LootPool> $pools
     */
    public function __construct(protected array $pools)
    {
    }

    /**
     * Creates a loot table with a single pool from a variadic list of entries
     * NOTE: If you need to specify the pool's roll, you can use {@link self::fromEntries()}.
     *
     * @param WeightedEntry<AcceptedPoolTypes> ...$entries
     */
    public static function fromVariadicEntries(mixed ...$entries): self
    {
        return new self(pools: [new LootPool($entries)]);
    }

    /**
     * Creates a loot table with a single pool from a list of entries
     * NOTE: If you do not need to specify the pool's roll, you can use {@link self::fromVariadicEntries()}
     * @param array<WeightedEntry<AcceptedPoolTypes>> $entries
     */
    public static function fromEntries(array $entries, Roll $roll): self
    {
        return new self([new LootPool(
            entries: $entries,
            roll: $roll
        )]);
    }

    /**
     * @return array<Item>
     */
    public function roll(): array
    {
        // Roll for each pool and reduce into one array
        return array_reduce(
            array: $this->pools,
            callback: fn(array $result, LootPool $pool) => [...$result, ...$pool->roll()],
            initial: []
        );
    }
}