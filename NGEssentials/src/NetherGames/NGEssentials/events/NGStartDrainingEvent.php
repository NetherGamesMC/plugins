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

namespace NetherGames\NGEssentials\events;

use pocketmine\event\server\ServerEvent;
use pocketmine\promise\Promise;
use pocketmine\utils\ObjectSet;

class NGStartDrainingEvent extends ServerEvent
{
    public function __construct(private readonly ObjectSet $promises)
    {

    }

    /**
     * Adds a promise to the waiting list for the server restart
     * Once all the promises that have been added have been completed,
     * the server will restart
     *
     * @phpstan-param Promise<null> $promise
     */
    public function addPromise(Promise $promise): void
    {
        $this->promises->add($promise);
    }
}