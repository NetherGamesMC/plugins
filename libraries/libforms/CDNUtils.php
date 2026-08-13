<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms;

use function str_replace;

class CDNUtils
{
    public const CDN_HOST = "https://cdn.nethergames.org";
    public const API_HOST = "https://api.ngmc.co";

    public static function getAvatarByHash(string $skinHash): string
    {
        return self::CDN_HOST . "/skins/" . $skinHash . "/head.png";
    }

    public static function getAvatarByName(string $name): string
    {
        return self::API_HOST . "/v2/players/" . str_replace(" ", "%20", $name) . "/head.png?copy=true";
    }

    public static function getMap(string $mapName): string
    {
        return self::CDN_HOST . "/img/maps/" . $mapName . ".jpg";
    }
}