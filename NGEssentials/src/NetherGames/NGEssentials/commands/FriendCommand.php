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
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class FriendCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('friend', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        if ($plugin->getServerManager()->getServerType() !== ServerManager::FACTIONS) {
            $this->setAliases(['f', 'friends']);
        } else {
            $this->setAliases(['friends']);
        }
        $this->setDescription('Add friends, view friends and party up!');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            $plugin = $this->getPlugin();

            if ($plugin->getServerManager()->enableSocialManager()) {
                $playerManager = $plugin->getPlayerManager();
                $friendsManager = $playerManager->getSocialManager()->getFriendsManager();

                if (isset($args[0])) {
                    $invited = $playerManager->getBestMatchingPlayer($args[0]);
                    if ($invited instanceof Player) {
                        $friendsManager->sendInvite($sender, $invited);
                    } else {
                        $sender->sendMessage('§cSorry, that player could not be found.');
                    }
                } else {
                    $friendsManager->sendFriendMenu($sender);
                }
            } else {
                $sender->sendMessage('§cThis server does not support social features.');
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}