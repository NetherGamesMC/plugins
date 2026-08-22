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

use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;

/**
 * Fired when a player changes their input mode.
 */
class PlayerInputChangeEvent extends PlayerEvent
{
    public function __construct(Player $player, private int $lastInputMode, private int $newInputMode)
    {
        parent::__construct($player);
    }

    public function getLastInputMode(): int
    {
        return $this->lastInputMode;
    }

    public function getNewInputMode(): int
    {
        return $this->newInputMode;
    }
}