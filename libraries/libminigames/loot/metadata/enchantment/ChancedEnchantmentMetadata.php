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

use libminigames\pool\WeightedEntry;
use libminigames\pool\WeightedPool;
use pocketmine\item\enchantment\Enchantment;

class ChancedEnchantmentMetadata extends WeightedEnchantmentMetadata
{
    public function __construct(Enchantment $enchantment, int $level, int|float $chance)
    {
        parent::__construct(
            enchantment: $enchantment,
            entries: [
                new WeightedEntry(0, WeightedPool::TOTAL_WEIGHT_SCALE - $chance),
                new WeightedEntry($level, $chance),
            ]
        );
    }
}