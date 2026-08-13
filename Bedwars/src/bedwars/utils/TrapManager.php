<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\utils;

use bedwars\BWTeam;
use bedwars\shops\Trap;
use pocketmine\utils\TextFormat;

class TrapManager
{
    public const MAX_QUEUED_TRAPS = 3;

    /** @var array<int, Trap> */
    private array $traps = [];
    private bool $trapActivated = false;

    public function __construct(private readonly BWTeam $team)
    {
    }

    public function getTeam(): BWTeam
    {
        return $this->team;
    }

    public function deactivateTrap(): void
    {
        $this->trapActivated = false;
    }

    /**
     * Adds a trap to the queue and returns a boolean indicating if the trap was added or not.
     */
    public function add(Trap $trap): bool
    {
        for ($slot = 0; $slot < self::MAX_QUEUED_TRAPS; $slot++) {
            // If an open slot is found, add the trap to it
            if (!isset($this->traps[$slot])) {
                $this->traps[$slot] = $trap;
                return true;
            }
        }
        return false;
    }


    /**
     * @param Player[] $intruders
     */
    public function activate(array $intruders): void
    {
        // If there aren't any traps to activate, return
        if (count($this->traps) <= 0) {
            return;
        }
        // Shift the trap queue and handle the trap
        /** @var Trap $trap */
        $trap = array_shift($this->traps);
        // If the trap is friendly, apply it to the team, otherwise apply it to the intruders
        $targets = $trap->friendly ? $this->getTeam()->getAlivePlayers() : $intruders;
        // Activate trap for each target
        foreach ($targets as $target) {
            $trap->activate($target);
        }

        foreach ($this->getTeam()->getAlivePlayers() as $player) {
            $player->sendTitle(TextFormat::BOLD . TextFormat::RED . "TRAP TRIGGERED!", "Your $trap->name has been set off!", 0, 60, 20);
            $player->sendMessage(TextFormat::BOLD . TextFormat::RED . "$trap->name was set off!");
        }

        $this->trapActivated = true;
    }

    public function hasTrapQueued(): bool
    {
        return isset($this->getTraps()[0]);
    }

    /**
     * @return array<int, Trap>
     */
    public function getTraps(): array
    {
        return $this->traps;
    }

    public function isFull(): bool
    {
        return count($this->traps) === self::MAX_QUEUED_TRAPS;
    }

    public function getNextTrapCost(): int
    {
        return match (count($this->traps)) {
            0 => 1,
            1 => 2,
            default => 4
        };
    }

    public function hasTrapActivated(): bool
    {
        return $this->trapActivated;
    }
}