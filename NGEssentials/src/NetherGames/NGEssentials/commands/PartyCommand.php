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

class PartyCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('party', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        if ($plugin->getServerManager()->getServerType() !== ServerManager::CREATIVE) {
            $this->setAliases(['p', 'parties']);
        } else {
            $this->setAliases(['parties']);
        }
        $this->setDescription('Party up with other people');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $plugin = $this->getPlugin();

        if ($sender instanceof Player) {
            if ($plugin->getServerManager()->enableSocialManager()) {
                $playerManager = $plugin->getPlayerManager();
                $partyManager = $playerManager->getSocialManager()->getPartyManager();

                if (isset($args[0])) {
                    $invited = $playerManager->getBestMatchingPlayer($args[0]);
                    if ($invited instanceof Player) {
                        $partyManager->invitePlayer($sender, $invited);
                    } else {
                        $sender->sendMessage('§cSorry, that player could not be found.');
                    }
                } else {
                    $partyManager->sendPartiesMenu($sender);
                }
            } else {
                $sender->sendMessage('§cThis server does not support social features.');
            }
        } else {
            $sender->sendMessage($plugin->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}