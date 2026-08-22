<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\utils;

use libminigames\events\player\PlayerMessageEvent;
use libminigames\session\GameSession;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Sends messages to players on behalf of libminigames.
 *
 * @internal This is a temporary transport until libminigames gets proper i18n support. See {@see PlayerMessageEvent}.
 * @todo Replace with a proper i18n system.
 */
final class Messages
{
    /**
     * @param array<string, string> $args
     */
    public static function send(Player $player, string $key, array $args = [], string $medium = 'actionbar', string $style = 'info', ?string $fallback = null): void
    {
        $event = PlayerMessageEvent::make($player, $key, $args, self::mediumToType($medium), $style, $fallback);
        $event->call();

        if ($event->isCancelled()) {
            return;
        }

        self::deliver($player, $event);
    }

    /**
     * @param Player[] $players
     * @param array<string, string> $args
     */
    public static function broadcast(array $players, string $key, array $args = [], string $medium = 'actionbar', string $style = 'info', ?string $fallback = null): void
    {
        foreach ($players as $player) {
            self::send($player, $key, $args, $medium, $style, $fallback);
        }
    }

    private static function mediumToType(string $medium): int
    {
        return match ($medium) {
            'chat' => TextType::TYPE_CHAT,
            'popup' => TextType::TYPE_POPUP,
            'tip' => TextType::TYPE_TIP,
            'title' => TextType::TYPE_TITLE,
            'toast' => TextType::TYPE_TOAST,
            'jukebox' => TextType::TYPE_JUKEBOX_POPUP,
            default => TextType::TYPE_ACTIONBAR,
        };
    }

    private static function deliver(Player $player, PlayerMessageEvent $event): void
    {
        if ($event->getMessage() === '') {
            return;
        }

        $message = $event->getMessage();

        if ($event->getMedium() !== TextType::TYPE_CHAT && !GameSession::getSession($player)->isPopupsEnabled()) {
            $player->sendMessage($message);
            return;
        }

        switch ($event->getMedium()) {
            case TextType::TYPE_CHAT:
                $player->sendMessage($message);
                break;
            case TextType::TYPE_ACTIONBAR:
                $player->sendActionBarMessage(TextUtils::center($message));
                break;
            case TextType::TYPE_POPUP:
                $player->sendPopup(TextUtils::center($message));
                break;
            case TextType::TYPE_TIP:
                $player->sendTip(TextUtils::center($message));
                break;
            case TextType::TYPE_TITLE:
                $player->sendTitle(TextUtils::center($message));
                break;
            case TextType::TYPE_TOAST:
                $player->sendToastNotification(TextUtils::center($message), $message);
                break;
            case TextType::TYPE_JUKEBOX_POPUP:
                $player->sendJukeboxPopup(TextUtils::center($message));
                break;
            default:
                $player->sendMessage($message);
                $player->sendMessage(TextFormat::RED . "Error: Invalid message type: " . $event->getMedium() . ". Please report this to a staff member.");
                break;
        }
    }
}