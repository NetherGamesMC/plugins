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

/**
 * A tiny registry for the glyphs (custom-font/icon characters) the engine may render.
 *
 * <p>libminigames does not ship any icon set: the glyph codepoints are defined by a resource
 * pack, and it is up to the consuming plugin/network to supply them. Plugins register the glyphs
 * their pack provides (typically during {@see \libminigames\Minigame::registerClasses()}).
 * Unregistered keys return the supplied fallback (or an empty string), so the engine renders
 * cleanly even without any pack.
 *
 * <p>Example:
 * <code>
 * // inside a plugin that ships the NetherGames resource pack
 * Icon::set('heart', \NetherGames\NGEssentials\utils\CustomIcon::HEART);
 * Icon::set('gamemode', \NetherGames\NGEssentials\utils\CustomIcon::GAMEMODE);
 * </code>
 *
 * <p>Keys currently consumed by the engine:
 * <ul>
 *   <li><code>heart</code> — health-tag glyph (see {@see \libminigames\session\GameSession::setHealthTag()})</li>
 *   <li><code>gamemode</code> - waiting-lobby mode row</li>
 *   <li><code>hourglass</code> - waiting-lobby status row</li>
 *   <li><code>players</code> - waiting-lobby player count row</li>
 *   <li><code>footer</code> - waiting-lobby footer (line 1)</li>
 *   <li><code>countdown.go</code>, <code>countdown.1</code> .. <code>countdown.5</code> - countdown title glyphs</li>
 *   <li><code>tag.winter</code>, <code>tag.halloween</code>, <code>tag.new</code>, <code>tag.summer</code> - map display-name tag icons</li>
 * </ul>
 */
final class Icon
{
    /** @var array<string, string> */
    private static array $icons = [];

    private function __construct()
    {
    }

    public static function set(string $key, string $glyph): void
    {
        self::$icons[$key] = $glyph;
    }

    public static function get(string $key, string $fallback = ''): string
    {
        return self::$icons[$key] ?? $fallback;
    }
}