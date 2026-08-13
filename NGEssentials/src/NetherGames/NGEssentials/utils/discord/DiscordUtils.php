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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils\discord;

use libDiscord\message\embed\Author;
use libDiscord\message\embed\Footer;
use libDiscord\message\embed\Thumbnail;

class DiscordUtils
{

    public const AVATAR_ENDPOINT = "https://api.ngmc.co/v1/players/:username/avatar";

    public static function asThumbnail(string $username): Thumbnail
    {
        return Thumbnail::simple(self::getAvatar($username), 64, 64);
    }

    /**
     * Returns the endpoint URL for a player's avatar
     *
     * @param string $username
     * @return string
     */
    public static function getAvatar(string $username): string
    {
        return str_replace(":username", str_replace(' ', '%20', $username), self::AVATAR_ENDPOINT);
    }

    public static function asAuthor(string $username): Author
    {
        return Author::create(
            $username,
            "",
            self::getAvatar($username),
            ""
        );
    }

    public static function asFooter(string $text, string $username): Footer
    {
        return Footer::simple(
            $text,
            self::getAvatar($username)
        );
    }

}