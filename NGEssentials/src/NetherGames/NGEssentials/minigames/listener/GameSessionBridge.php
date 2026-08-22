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

use libminigames\session\GameSession;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\events\PlayerInputChangeEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\social\party\objects\Party;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\event\Listener;
use function method_exists;

/**
 * Populates {@see GameSession} from NetherGames player state so the generic engine can operate
 * without knowing about NetherGames.
 */
final class GameSessionBridge implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority MONITOR
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $session = GameSession::getSession($player);

        $session->setInputMode($player->getInputMode());
        $session->setClassicUi(Utils::hasClassicUI($player));
        $session->setPopupsEnabled($this->plugin->getPlayerData()->getGameSettings()->getBool($player, GameSettings::POPUP_MESSAGES));
        $session->setTouchOnlyPreference($this->plugin->getPlayerData()->getGameSettings()->getBool($player, GameSettings::TOUCH_ONLY));

        if (method_exists($player, 'isArmorInvisible')) {
            $session->setArmorInvisible($player->isArmorInvisible());
        }

        $partyManager = $this->plugin->getPlayerManager()->getSocialManager()->getPartyManager();
        $party = $partyManager->getParty($player, false);

        $session->setGroupUUID($party?->getUuid());
    }

    /**
     * @param PlayerInputChangeEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerInputChange(PlayerInputChangeEvent $event): void
    {
        GameSession::getSession($event->getPlayer())->setInputMode($event->getNewInputMode());
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}