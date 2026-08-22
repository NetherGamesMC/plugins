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

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/**
 * Fired whenever a kill message is requested for a player that was killed in an arena.
 *
 * <p>Handlers may replace the kill message via {@see PlayerKillEvent::setKillMessage()}.
 */
class PlayerKillEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(
        Player          $victim,
        private ?Player $killer,
        private int     $cause,
        private string  $killMessage
    )
    {
        parent::__construct($victim);
    }

    public function getKiller(): ?Player
    {
        return $this->killer;
    }

    /**
     * The {@link EntityDamageEvent} cause for this kill.
     */
    public function getCause(): int
    {
        return $this->cause;
    }

    public function getKillMessage(): string
    {
        return $this->killMessage;
    }

    public function setKillMessage(string $killMessage): void
    {
        $this->killMessage = $killMessage;
    }
}