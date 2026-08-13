<?php

declare(strict_types=1);

namespace lobby\player;

use lobby\utils\BaseTrait;
use lobby\utils\Items;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlayerManager
{
    use BaseTrait;

    public function setupPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        $ess = $this->getNGEssentials();
        $playerData = $ess->getPlayerData();
        $playerManager = $ess->getPlayerManager();

        if (MySQLCredentials::isDatabaseOnline()) {
            $playerManager->setStatsBar($player);

            if ($player->hasPermission(Permissions::RANK_ULTRA) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK)) {
                $player->setAllowFlight(true);
            }

            if ($playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && $player->hasPermission(Permissions::RANK_TITAN) && !$playerData->getBool($player, PlayerData::NICK) && !$playerData->getBool($player, PlayerData::TRACK)) {
                $player->getServer()->broadcastMessage($player->getNameTag() . TextFormat::RESET . TextFormat::GOLD . " has joined the server!", $player->getWorld()->getPlayers());
            }
        }

        $player->setEnergized();

        if (($spectatedName = $playerData->getString($player, PlayerData::TRACK)) === "") {
            Items::setLobbyInventory($player);
        } else {
            $playerManager->getEnforcementHandler()->setTracking($player, $spectatedName, false);
        }
    }
}