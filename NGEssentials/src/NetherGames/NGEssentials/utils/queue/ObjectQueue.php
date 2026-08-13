<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */

namespace NetherGames\NGEssentials\utils\queue;

/**
 * @template T
 */
class ObjectQueue
{
    /** @var T[] */
    protected array $elements;

    public function __construct()
    {
        $this->elements = [];
    }

    /**
     * @param T $object
     */
    public function enqueue($object): void
    {
        $this->elements[] = $object;
    }

    /**
     * @param int $position
     * @param T $object
     */
    public function emplace(int $position, $object): void
    {
        $this->elements[$position] = $object;
    }

    /**
     * @return T
     */
    public function current()
    {
        return $this->elements[0] ?? null;
    }

    /**
     * @return T
     */
    public function last()
    {
        return $this->elements[$this->getLastKey()] ?? null;
    }

    public function getLastKey(): int
    {
        return array_key_last($this->elements) ?? 0;
    }

    /**
     * @return T
     */
    public function dequeue()
    {
        return array_shift($this->elements);
    }

    public function clear(): void
    {
        $this->elements = [];
    }

    public function size(): int
    {
        return count($this->elements);
    }
}