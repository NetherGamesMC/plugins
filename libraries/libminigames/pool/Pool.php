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
 * The Pool interface is used as a way to pull an element from a pool of element.
 *
 * For example, if you had an item pool consisting of:
 *   - Diamond Helmet
 *   - Iron Chestplate
 *   - Chain Chestplate
 *   - Gold Boots
 *
 * You could use an implementation of this interface to pull one of the items.
 * @template TValue
 */
interface Pool
{
    /**
     * Returns $amount elements from the pool.
     *
     * @param int $amount
     * @return TValue|array<TValue>
     */
    public function pull(int $amount = 1): mixed;
}