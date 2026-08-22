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


namespace NetherGames\NGEssentials\minigames;

use NetherGames\NGEssentials\NGEssentials;
use libminigames\utils\Icon;
use NetherGames\NGEssentials\minigames\listener\AccessGatesListener;
use NetherGames\NGEssentials\minigames\listener\CosmeticsListener;
use NetherGames\NGEssentials\minigames\listener\EntityCleanupListener;
use NetherGames\NGEssentials\minigames\listener\GameSessionBridge;
use NetherGames\NGEssentials\minigames\listener\LeaderboardListener;
use NetherGames\NGEssentials\minigames\listener\MatchmakingListener;
use NetherGames\NGEssentials\minigames\listener\MessageListener;
use NetherGames\NGEssentials\minigames\listener\PartyListener;
use NetherGames\NGEssentials\minigames\listener\PersistenceListener;
use NetherGames\NGEssentials\minigames\listener\ReplayListener;
use NetherGames\NGEssentials\minigames\listener\RewardsListener;
use NetherGames\NGEssentials\minigames\listener\StreakListener;
use NetherGames\NGEssentials\utils\CustomIcon;

/**
 * Coordinates all NetherGames-specific behavior for libminigames by subscribing to the
 * event surface the library exposes. Registration is idempotent.
 */
final class MinigamesModule
{
    private static bool $registered = false;

    private function __construct()
    {
    }

    /**
     * Initializes the module for the given plugin. Safe to call multiple times.
     */
    public static function register(NGEssentials $plugin): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        self::registerIcons();

        $pluginManager = $plugin->getServer()->getPluginManager();

        $pluginManager->registerEvents(new GameSessionBridge($plugin), $plugin);
        $pluginManager->registerEvents(new MatchmakingListener($plugin), $plugin);
        $pluginManager->registerEvents(new PartyListener($plugin), $plugin);
        $pluginManager->registerEvents(new RewardsListener($plugin), $plugin);
        $pluginManager->registerEvents(new CosmeticsListener($plugin), $plugin);
        $pluginManager->registerEvents(new MessageListener($plugin), $plugin);
        $pluginManager->registerEvents(new PersistenceListener($plugin), $plugin);
        $pluginManager->registerEvents(new ReplayListener($plugin), $plugin);
        $pluginManager->registerEvents(new AccessGatesListener($plugin), $plugin);
        $pluginManager->registerEvents(new EntityCleanupListener($plugin), $plugin);
        $pluginManager->registerEvents(new LeaderboardListener($plugin), $plugin);
        $pluginManager->registerEvents(new StreakListener($plugin), $plugin);
    }

    /**
     * Supplies the glyph set the libminigames engine renders (see {@see Icon}).
     */
    private static function registerIcons(): void
    {
        Icon::set('heart', CustomIcon::HEART);
        Icon::set('gamemode', CustomIcon::GAMEMODE);
        Icon::set('hourglass', CustomIcon::HOURGLASS);
        Icon::set('players', CustomIcon::PLAYERS);
        Icon::set('footer', CustomIcon::NETHERGAMES);

        Icon::set('countdown.go', CustomIcon::GO);
        Icon::set('countdown.1', CustomIcon::ONE);
        Icon::set('countdown.2', CustomIcon::TWO);
        Icon::set('countdown.3', CustomIcon::THREE);
        Icon::set('countdown.4', CustomIcon::FOUR);
        Icon::set('countdown.5', CustomIcon::FIVE);

        Icon::set('tag.winter', CustomIcon::CHRISTMAS_HAT);
        Icon::set('tag.halloween', CustomIcon::PUMPKIN);
        Icon::set('tag.new', CustomIcon::NEW);
        Icon::set('tag.summer', CustomIcon::SUN);
    }
}