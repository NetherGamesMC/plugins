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

use libminigames\loot\metadata\count\CountMetadata;
use libminigames\loot\metadata\count\RandomCountMetadata;
use libminigames\loot\metadata\count\WeightedCountMetadata;
use libminigames\loot\metadata\enchantment\RandomEnchantmentMetadata;
use libminigames\loot\metadata\enchantment\WeightedEnchantmentMetadata;
use libminigames\loot\metadata\LootEntryMetadata;
use libminigames\pool\WeightedEntry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use function is_array;

/**
 * @extends WeightedEntry<Item|array<Item>>
 */
class LootEntry extends WeightedEntry implements Lootable
{

    /**
     * @param Item|array<Item> $value
     * @param array<LootEntryMetadata> $metadata
     */
    public function __construct(Item|array $value, int|float $weight, protected ?CountMetadata $countMetadata = null, protected array $metadata = [])
    {
        parent::__construct($value, $weight);
    }

    /**
     * Sets the count of the item based on a random amount between `$minimum` and `$maximum`
     */
    public function withRandomAmount(int $minimum, int $maximum): self
    {
        $this->countMetadata = new RandomCountMetadata($minimum, $maximum);
        return $this;
    }

    /**
     * Sets the count of the item based on the weighted entries
     * @param array<WeightedEntry<int>> $entries
     */
    public function withWeightedAmount(array $entries): self
    {
        $this->countMetadata = new WeightedCountMetadata($entries);
        return $this;
    }

    /**
     * Adds an enchantment to the item based on a random amount between `$minimum` and `$maximum`
     */
    public function withRandomEnchantment(Enchantment $enchantment, int $minimum, int $maximum): self
    {
        $this->metadata[] = new RandomEnchantmentMetadata($enchantment, $minimum, $maximum);
        return $this;
    }

    /**
     * Adds an enchantment to the item based on the weighted entries
     * @param array<WeightedEntry<int>> $entries
     */
    public function withWeightedEnchantment(Enchantment $enchantment, array $entries): self
    {
        $this->metadata[] = new WeightedEnchantmentMetadata($enchantment, $entries);
        return $this;
    }


    /**
     * Appends a variable amount of metadata to the entry's metadata
     */
    public function withMetadata(LootEntryMetadata ...$metadata): self
    {
        foreach ($metadata as $entryMetadata) {
            $this->metadata[] = $entryMetadata;
        }
        return $this;
    }

    /**
     * @return array<Item>
     */
    public function roll(): array
    {
        $generated = $this->generate();
        return is_array($generated) ? $generated : [$generated];
    }

    /**
     * @return Item|array<Item>
     */
    public function generate(): Item|array
    {
        return match (true) {
            is_array($this->value) => array_map(
                callback: fn(Item $item) => $this->apply(clone $item),
                array: $this->value
            ),
            default => $this->apply(clone $this->value)
        };
    }

    /**
     * Applies the metadata to the item
     */
    public function apply(Item $item): Item
    {
        foreach ($this->metadata as $entryMetadata) {
            $entryMetadata->apply($item);
        }
        // Apply count metadata if it exists
        $this->countMetadata?->apply($item);
        return $item;
    }


}