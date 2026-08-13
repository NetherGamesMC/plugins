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

namespace libminigames\loot\metadata\enchantment;

use libminigames\loot\metadata\LootEntryMetadata;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

abstract class EnchantmentMetadata implements LootEntryMetadata
{
    public function __construct(protected Enchantment $enchantment)
    {
    }

    public function apply(Item $item): void
    {
        // Don't apply negative enchantments
        if (($level = $this->generate()) <= 0) {
            return;
        }
        $item->addEnchantment(new EnchantmentInstance(
            enchantment: $this->enchantment,
            level: $level
        ));
    }

    /**
     * Generates the enchantment level to be applied
     */
    public abstract function generate(): int;

}