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
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function in_array;

class ReconnectCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('reconnect', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases(['back', 'rejoin']);
        $this->setDescription('Command used for reconnecting to a Bedwars match');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $plugin = $this->getPlugin();

        if ($sender instanceof Player) {
            $playerData = $plugin->getPlayerData();

            if (($lastServer = $playerData->getString($sender, PlayerData::LAST_SERVER)) === '') {
                $sender->sendMessage('§cThe last server you were detected to be playing on is not available.');
            } else {
                $server = $plugin->getServerManager()->getServer($lastServer);

                if ($server !== null) {
                    if (in_array($server->getCluster()->getServerType(), [ServerManager::BW, ServerManager::TB, ServerManager::UHC, ServerManager::CQ], true)) {
                        $playerData->setValue($sender, PlayerData::RECONNECT, true);
                        $plugin->getPlayerManager()->transferPlayer($sender, $server, '', true);
                    } else {
                        $sender->sendMessage('§cThe last server you were detected to be playing on is not able to reconnect to.');
                    }
                }
            }
        } else {
            $sender->sendMessage($plugin->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}