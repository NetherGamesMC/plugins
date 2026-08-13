<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use bridges\utils\InvMenuListenerUtils;
use libminigames\Minigame;
use libminigames\MinigameListener;
use muqsit\invmenu\inventory\InvMenuInventory;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\utils\TextFormat;

class BridgeListener extends MinigameListener
{
    /**
     * @param NGJoinEvent $event
     *
     * @priority NORMAL
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            $player = $event->getPlayer();
            $plugin = $this->getPlugin();
            $ess = $plugin->getEssentials();

            if ($ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                $ess->getPlayerData()->setValue($player, PlayerData::RECONNECT, false);

                if (($arena = $plugin->getArenaByXuid($player->getXuid())) !== null && $arena->isRunning()) {
                    /** @var BridgeArena $arena */
                    if (($team = $arena->getTeamByXuid($player->getXuid())) !== null) {
                        /** @var BridgeTeam $team */
                        $team->reconnectPlayer($player);
                        return;
                    }
                }

                $player->sendMessage(TextFormat::RED . "Couldn't connect you to that match, so you were put in another Bedwars match!");

                if ($plugin->isStandAloneGame()) {
                    $plugin->joinArena($player);
                }
            }
        }
    }

    /**
     * @return TheBridge
     */
    public function getPlugin(): Minigame
    {
        /** @var TheBridge $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority NORMAL
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            $player = $event->getPlayer();
            $ess = $this->getPlugin()->getEssentials();

            if (!$ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                parent::onNGLogin($event);
            }
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) {
            $inventory = $event->getInventory();

            if ($arena->isRunning()) {
                $arena->getListener()->onInventoryOpen($event);
            } else if ($inventory instanceof InvMenuInventory) {
                $event->uncancel();
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority NORMAL
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($player = $event->getTransaction()->getSource())) !== null) {
            $openWindow = $event->getTransaction()->getSource()->getCurrentWindow();

            $inventory = $event->getTransaction()->getInventories();
            $isInvMenu = array_filter($inventory, static function ($inventory) {
                return !($inventory instanceof PlayerInventory);
            });

            if ($arena->isSpectator($player)) {
                $event->cancel();
            } elseif ($arena->isRunning()) {
                $arena->getListener()->onInventoryTransaction($event);
            } else if (count($isInvMenu) === 2 || ((count($inventory) === 1) && !($inventory[array_key_first($inventory)] instanceof PlayerInventory))) {
                $event->uncancel();
            } else {
                if ($openWindow !== null) {
                    InvMenuListenerUtils::protectionChecks($player);
                }

                $event->cancel();
            }
        }
    }
}