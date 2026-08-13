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
 * @extends ObjectQueue<int>
 */
class TickQueue extends ObjectQueue
{
    /**
     * Returns all elements that have been pushed after the $timestamp.
     * Warning: it stops after the first successful result.
     * If the elements are not in the correct order, it'll require multiple runs.
     *
     * @param float $tick
     * @return $this
     */
    public function filterAfterTick(float $tick): self
    {
        while (isset($this->elements[0]) && $this->elements[0] < $tick) {
            $this->dequeue();
        }
        return $this;
    }
}