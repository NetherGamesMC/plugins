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

use libminigames\events\MinigameQuitEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class LobbyCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('lobby', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases(['hub', 'spawn', 'l']);
        $this->setDescription('Return to the lobby');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $plugin = $this->getPlugin();

        if ($sender instanceof Player) {
            $playerManager = $plugin->getPlayerManager();
            $serverManager = $plugin->getServerManager();

            if (($arena = $playerManager->isInArena($sender, true)) === false) {
                if ($serverManager->getServerType() === ServerManager::LOBBY) {
                    $sender->teleport($serverManager->getSpawn());
                } else {
                    $playerManager->transferPlayer($sender);
                }
            } else {
                $arena->removePlayer($sender, MinigameQuitEvent::LEAVE);
            }

            $plugin->getPlayerData()->setValue($sender, PlayerData::TRACK, '');
        } else {
            $sender->sendMessage($plugin->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}
