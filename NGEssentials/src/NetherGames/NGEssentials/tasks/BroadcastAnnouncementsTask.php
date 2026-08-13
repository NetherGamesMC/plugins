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

use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerData;
use pocketmine\player\Player;
use pocketmine\world\World;
use function array_rand;

class BroadcastAnnouncementsTask extends BaseTask
{
    public function onRun(): void
    {
        if (count($announcements = $this->getPlugin()->getServerData()->getArray(ServerData::ANNOUNCEMENTS)) !== 0) {
            /** @var World $defaultWorld */
            $defaultWorld = $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();
            $plugin = $this->getPlugin();
            $players = array_filter(array: $defaultWorld->getPlayers(), callback: fn(Player $player) => $plugin->getPlayerData()->getBool($player, PlayerData::ANNOUNCEMENTS));
            $this->getPlugin()->getServer()->broadcastMessage($announcements[array_rand($announcements)], $players);
        }
    }
}
