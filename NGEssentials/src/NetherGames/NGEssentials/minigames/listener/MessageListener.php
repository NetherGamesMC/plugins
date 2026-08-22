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

use libminigames\events\player\PlayerMessageEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\event\Listener;

/**
 * Resolves libminigames message keys and falls back to the local translation files per recipient.
 *
 * @internal Temporary bridge until libminigames ships proper i18n support.
 */
final class MessageListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerMessageEvent $event
     *
     * @priority NORMAL
     */
    public function onMessage(PlayerMessageEvent $event): void
    {
        $player = $event->getPlayer();

        $message = Translator::getTranslationPlayer(
            $player,
            $event->getKey(),
            Translator::TYPE_DEFAULT,
            ...$event->getArgs()
        );

        // Translator returns the key itself when a translation is missing; fall back rather
        // than sending the raw key.
        $event->setMessage($message !== $event->getKey() ? $message : ($event->getFallback() ?? ''));
        $event->cancel();
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}