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
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\events\player;

use pocketmine\player\Player;

/**
 * Fired whenever a player's in-memory stats change inside an arena.
 */
class PlayerStatChangeEvent extends PlayerEvent
{
    public function __construct(Player $player, private int $statId, private string $statName, private int $oldValue, private int $newValue)
    {
        parent::__construct($player);
    }

    public function getStatId(): int
    {
        return $this->statId;
    }

    public function getStatName(): string
    {
        return $this->statName;
    }

    public function getOldValue(): int
    {
        return $this->oldValue;
    }

    public function getNewValue(): int
    {
        return $this->newValue;
    }
}