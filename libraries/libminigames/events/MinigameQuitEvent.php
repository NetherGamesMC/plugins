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

namespace libminigames\events;

use libminigames\Arena;
use pocketmine\player\Player;

class MinigameQuitEvent extends MinigameEvent
{
    public const LEAVE = 1; //PLAYER LEFT THE MATCH
    public const END = 2; //PLAYER IS DONE IN THE MATCH
    public const FINISH = 3; //ARENA IS FINISHED
    public const DISCONNECT = 4; //CLIENT DISCONNECT FROM THE SERVER
    public const PARTY = 5;
    public const DISCONNECT_KICK = 6;

    /** @var int */
    private int $reason;

    public function __construct(Player $player, Arena $arena, int $gameType, int $gameId, int $reason)
    {
        parent::__construct($player, $arena, $gameType, $gameId);
        $this->reason = $reason;
    }

    public function getReason(): int
    {
        return $this->reason;
    }
}