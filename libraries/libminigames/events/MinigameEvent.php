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
use libminigames\Minigame;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\player\Player;

class MinigameEvent extends PluginEvent implements Cancellable
{
    use CancellableTrait;

    /** @var Player */
    protected Player $player;
    /** @var Arena */
    private Arena $arena;
    /** @var int */
    private int $gameType;
    /** @var int */
    private int $gameId;

    public function __construct(Player $player, Arena $arena, int $gameType, int $gameId)
    {
        $this->player = $player;
        $this->arena = $arena;
        $this->gameType = $gameType;
        $this->gameId = $gameId;
        parent::__construct(Minigame::getInstance());
    }

    /**
     * Get the player for which this event was called.
     *
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGameId(): int
    {
        return $this->gameId;
    }

    public function getGameType(): int
    {
        return $this->gameType;
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }
}