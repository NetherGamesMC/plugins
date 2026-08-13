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

namespace NetherGames\NGEssentials\tasks;

use NetherGames\NGEssentials\ServerManager;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class ClusterQueueTask extends Task
{

    public function __construct(private ServerManager $serverManager)
    {
    }

    public function onRun(): void
    {
        $serverManager = $this->getServerManager();

        foreach ($serverManager->getQueuedPlayers() as $uniqueId => $players) {
            $cluster = $serverManager->getClusterByClusterUniqueId($uniqueId);

            foreach ($players as $index => $player) {
                if (!$player->isConnected()) {
                    continue;
                }

                if ($index === 0) {
                    $serverManager->getPlugin()->getPlayerManager()->transferPlayer($player, $cluster);
                }

                $player->sendJukeboxPopup(TextFormat::GOLD . 'You are ' . TextFormat::AQUA . '#' . ($index + 1) . TextFormat::GOLD . ' in the queue!');
            }
        }
    }

    public function getServerManager(): ServerManager
    {
        return $this->serverManager;
    }
}