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

use libminigames\events\player\PlayerLoginEvent;
use libminigames\events\player\PlayerRequeueEvent;
use libminigames\Minigame;
use libminigames\session\GameSession;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\social\party\objects\Party;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_key_first;

/**
 * Restores NetherGames matchmaking/queueing behavior on top of the generic engine: groups join as
 * a unit (sizing from the party total, including offline members), private games are created for
 * parties that request them, and requeues reuse the party size.
 */
final class MatchmakingListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerLoginEvent $event
     *
     * @priority HIGHEST
     */
    public function onLogin(PlayerLoginEvent $event): void
    {
        $game = $this->plugin->getServerManager()->getGamePlugin();
        if ($game === null || !$game->isStandAloneGame()) {
            return;
        }

        $player = $event->getPlayer();
        $partyManager = $this->plugin->getPlayerManager()->getSocialManager()->getPartyManager();
        $party = $partyManager->getParty($player, false);

        if ($party !== null) {
            GameSession::getSession($player)->setGroupUUID($party->getUuid());
        }

        if ($game->getArena($player) !== null) {
            return;
        }

        if ($party?->hasPrivateGames()) {
            if ($partyManager->isPartyCreator($player) && $game->getArena($player) === null) {
                $this->joinPrivate($game, $player, $party);

                return;
            }
        }

        $game->joinArena($player, -1, $party?->getTotalMembers() ?? 1);
    }

    /**
     * @param PlayerRequeueEvent $event
     *
     * @priority NORMAL
     */
    public function onRequeue(PlayerRequeueEvent $event): void
    {
        $partyManager = $this->plugin->getPlayerManager()->getSocialManager()->getPartyManager();
        $party = $partyManager->getParty($event->getPlayer(), false);

        if ($party !== null) {
            GameSession::getSession($event->getPlayer())->setGroupUUID($party->getUuid());
            $event->setSize($party->getTotalMembers());
        } else {
            GameSession::getSession($event->getPlayer())->setGroupUUID(null);
        }
    }

    private function joinPrivate(Minigame $game, Player $player, Party $party): void
    {
        $key = array_key_first($game->getModes());
        if ($key === null) {
            $player->sendMessage(TextFormat::RED . "Something went wrong.");

            return;
        }

        $arena = $game->createPrivateArena($key, $player);
        if ($arena === null) {
            return;
        }
        $arena->addPlayer($player);

        foreach ($party->getMembers() as $memberName) {
            $member = $this->plugin->getServer()->getPlayerExact($memberName);
            if ($member instanceof Player && $member->isConnected() && $game->getArena($member) === null) {
                $arena->addPlayer($member);
            }
        }
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}