<?php
declare(strict_types=1);

namespace uhc;

use libminigames\events\player\PlayerLoginEvent;
use libminigames\events\player\PlayerQuitEvent;
use libminigames\MinigameListener;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\utils\TextFormat;

class UHCListener extends MinigameListener
{
    /**
     * @param NGJoinEvent $event
     *
     * @priority NORMAL
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            /** @var UHC $plugin */
            $plugin = $this->getPlugin();
            /** @var NGPlayer $player */
            $player = $event->getPlayer();
            $ess = NGEssentials::getInstance();

            if ($ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                $ess->getPlayerData()->setValue($player, PlayerData::RECONNECT, false);

                if (($team = $plugin->getTeamByXuid($player->getXuid())) !== null && $team->getArena()->isRunning()) {
                    $team->reconnectPlayer($player);
                    return;
                }

                $player->sendMessage(TextFormat::RED . "Couldn't connect you to that match, so you were put in another Bedwars match!");

                if ($plugin->isStandAloneGame()) {
                    $plugin->joinArena($player);
                }
            }
        }
    }

    /**
     * @param PlayerLoginEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            $player = $event->getPlayer();
            $ess = NGEssentials::getInstance();

            if (!$ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                parent::onPlayerLogin($event);
            }
        }
    }

    public function onMinigameQuit(PlayerQuitEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $player->toggleGameRule("showcoordinates", false);
        $player->getXpManager()->setXpAndProgress(0, 0.0);
    }
}