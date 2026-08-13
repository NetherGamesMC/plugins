<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer;

use libminigames\Command;
use pocketmine\player\Player;

class SCCommand extends Command
{
    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        return false;
    }
}