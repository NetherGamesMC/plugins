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

use libforms\FormManager;
use libforms\elements\Button;
use libminigames\events\player\PlayerQuitEvent;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_key_first;
use function array_map;
use function count;
use function implode;

/**
 * Keeps parties coordinated when players leave an arena: only the party leader can take the whole
 * group out of a running match; members are kept in the game until the host decides otherwise.
 */
final class PartyListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority HIGHEST
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $reason = $event->getReason();
        if ($reason !== PlayerQuitEvent::LEAVE && $reason !== PlayerQuitEvent::END) {
            return;
        }

        $player = $event->getPlayer();
        $arena = $event->getArena();
        $playerManager = $this->plugin->getPlayerManager();
        $partyManager = $playerManager->getSocialManager()->getPartyManager();
        $party = $partyManager->getParty($player, false);

        if ($party === null) {
            return;
        }

        if (!$partyManager->isPartyCreator($player)) {
            $event->cancel();
            $player->sendMessage(TextFormat::RED . 'You can\'t go back to the lobby while you\'re in a party. Wait for your party host to decide when to return!');

            return;
        }

        $ingamePlayers = $arena->isRunning() ? array_filter($partyManager->getPlayers($party), static fn(Player $member) => !$arena->isSpectator($member) && $member !== $player) : [];

        if (count($ingamePlayers) === 0) {
            return;
        }

        $event->cancel();

        $form = FormManager::createModalForm($player);
        if ($form === null) {
            return;
        }

        $form->setTitle('Quit the match');

        if (count($ingamePlayers) === 1) {
            $member = $ingamePlayers[array_key_first($ingamePlayers)];
            $form->setContent($member->getDisplayName() . ' is still in the game. Are you sure you wish to take them to the lobby?');
        } else {
            $names = array_map(static fn(Player $p): string => $p->getDisplayName(), $ingamePlayers);
            $form->setContent(implode(', ', $names) . ' are still in the game. Are you sure you wish to take them to the lobby?');
        }

        $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GREEN . 'Yes', function (Player $player) use ($arena): void {
            $players = $arena->getPlayers();
            foreach ($players as $p) {
                if ($p->getXuid() === $player->getXuid()) {
                    continue;
                }
                $arena->removePlayer($p, PlayerQuitEvent::PARTY, true);
            }
            $arena->removePlayer($player, PlayerQuitEvent::END, true);
        }));

        $form->setButton2(new Button(TextFormat::BOLD . TextFormat::RED . 'No'));

        $form->sendForm();
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}