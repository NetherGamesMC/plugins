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
 * Fired when a player requeues into another game after a match.
 *
 * <p>Handlers may adjust the requeue size via {@see PlayerRequeueEvent::setSize()} to account for
 * grouped players (e.g. a party).
 */
class PlayerRequeueEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Player $player, private Arena $arena, private string $mode, private int $size = 1)
    {
        parent::__construct($player);
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * The amount of players requeueing together as a unit.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }
}