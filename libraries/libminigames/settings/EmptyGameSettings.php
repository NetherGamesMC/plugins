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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libminigames\settings;

use pocketmine\player\Player;

/**
 * `EmptyGameSettings` is a class that is created when no game settings is passed to the `Arena` constructor.
 *
 * This class implements *no* game settings whatsoever.
 */
final class EmptyGameSettings extends GameSettings
{
    /**
     * EmptyGameSettings is only used when no settings are passed to the constructor.
     * This means that the game should not save the settings to the player's data
     *
     * @param Player $player
     * @param string $serverType
     * @param string $gameType
     * @return void
     */
    public function saveToPlayer(Player $player, string $serverType, string $gameType): void
    {
    }
}