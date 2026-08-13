<?php
/**
 *   _ _ _                                _
 *  | (_) |                              (_)
 *  | |_| |__   __ _ ___ _   _ _ __   ___ _  ___
 *  | | | '_ \ / _` / __| | | | '_ \ / __| |/ _ \
 *  | | | |_) | (_| \__ \ |_| | | | | (__| | (_) |
 *  |_|_|_.__/ \__,_|___/\__, |_| |_|\___|_|\___/
 *                        __/ |
 *                       |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author driesboy
 *
 */
declare(strict_types=1);

namespace libasyncio\blocks;

use Closure;
use NetherGames\NGEssentials\thread\NGThreadPool;
use pocketmine\block\Block;
use pocketmine\world\World;

class AsyncBlockManager
{
    /**
     * This will execute the block placements
     * It collects all chunks affected by this replacement and schedules the task afterwards
     *
     * @param Selection $selection
     * @param World $world
     * @param Closure|null $closure Closure that will be executed when the process finished
     */
    public static function executeSet(Selection $selection, World $world, ?Closure $closure = null): void
    {
        NGThreadPool::getInstance()->submitTask(new AsyncBlockSetOperation($selection, $world, $closure));
    }

    /**
     * This will execute the block placements
     * It collects all chunks affected by this replacement and schedules the task afterwards
     *
     * @param Selection $selection
     * @param Block[] $replaces
     * @param World $world
     * @param Closure|null $closure Closure that will be executed when the process finished
     */
    public static function executeReplace(Selection $selection, array $replaces, World $world, ?Closure $closure = null): void
    {
        NGThreadPool::getInstance()->submitTask(new AsyncBlockReplaceOperation($selection, $replaces, $world, $closure));
    }
}