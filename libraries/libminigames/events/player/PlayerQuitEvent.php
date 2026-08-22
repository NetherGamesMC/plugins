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

use libminigames\Arena;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/**
 * Fired when a player is being removed from an arena, before they are cleaned up.
 */
class PlayerQuitEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public const LEAVE = 1; //PLAYER LEFT THE MATCH
    public const END = 2; //PLAYER IS DONE IN THE MATCH
    public const FINISH = 3; //ARENA IS FINISHED
    public const DISCONNECT = 4; //CLIENT DISCONNECT FROM THE SERVER
    public const PARTY = 5;
    public const DISCONNECT_KICK = 6;

    public function __construct(Player $player, private Arena $arena, private int $reason)
    {
        parent::__construct($player);
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }

    public function getReason(): int
    {
        return $this->reason;
    }
}