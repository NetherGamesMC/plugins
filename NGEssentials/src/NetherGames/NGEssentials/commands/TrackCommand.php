<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\commands;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use function count;

class TrackCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('track', $plugin);

        $this->setPermission(Permissions::RANK_TRAINEE);
        $this->setPermissionMessage('command.reserved.staff');
        $this->setDescription('Track a player');
        $this->setUsage('/track <player>');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof NGPlayer) {
            if (count($args) === 1) {
                $enforcementHandler = $this->getPlugin()->getPlayerManager()->getEnforcementHandler();

                if ($this->getPlugin()->getPlayerManager()->isInArena($sender)) {
                    $sender->sendMessage("§cYou can't run this command in-game.");
                } elseif ($args[0] === 'off' || $args[0] === 'exit') {
                    $enforcementHandler->setTracking($sender, false);
                } else {
                    $enforcementHandler->setTracking($sender, $args[0]);
                }
            } else {
                throw new InvalidCommandSyntaxException();
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }

}