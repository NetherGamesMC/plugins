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
 * @author driesboy
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\minigames\listener;

use libminigames\Arena;
use libminigames\events\player\PlayerCreatePrivateGameEvent;
use libminigames\events\player\PlayerJoinEvent;
use libminigames\events\player\PlayerSelectTeamEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Applies NetherGames permission/tracking gates to the engine's decision points.
 */
final class AccessGatesListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * Players in tracking mode may not join a game.
     *
     * @param PlayerJoinEvent $event
     *
     * @priority HIGHEST
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        if ($this->plugin->getPlayerData()->getString($event->getPlayer(), PlayerData::TRACK) !== '') {
            $event->cancel();
            $event->getPlayer()->sendMessage(TextFormat::RED . "You're currently in tracking mode. Leave to join a game.");
        }
    }

    /**
     * Team selection is gated behind the Emerald/Legend ranks, as before the decoupling.
     *
     * @param PlayerSelectTeamEvent $event
     *
     * @priority HIGHEST
     */
    public function onSelectTeam(PlayerSelectTeamEvent $event): void
    {
        $player = $event->getPlayer();
        if ($event->getArena()->isPrivateGame()) {
            return;
        }

        if (!$player->hasPermission(Permissions::RANK_EMERALD)) {
            $event->cancel();
            $event->setError('§l§aEMERALD §r§cor §l§bLEGEND §r§crank required to choose teams! Purchase at §bngmc.co/store§c!');
        }
    }

    /**
     * @param PlayerCreatePrivateGameEvent $event
     *
     * @priority HIGHEST
     */
    public function onCreatePrivateGame(PlayerCreatePrivateGameEvent $event): void
    {
        if (!$event->getPlayer()->hasPermission(Permissions::RANK_LEGEND)) {
            $event->cancel();
            $event->getPlayer()->sendMessage(TextFormat::RED . "You need the §l§bLEGEND§r§c rank to create private games. Purchase it at §bCorpus.co/store§c!");
        }
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}