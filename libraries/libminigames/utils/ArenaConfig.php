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
 * @author AlicanCopur
 *
 */

namespace libminigames\utils;

use libminigames\Arena;
use pocketmine\utils\Config;
use pocketmine\world\World;

/**
 * This class should be extended by mini-game's own arena config.
 * See {@link Minigame::getArenaConfig()}
 */
abstract class ArenaConfig
{
    public const TAG_WINTER = 'winter';
    public const TAG_SUMMER = 'summer';
    public const TAG_NEW = 'new';
    public const TAG_HALLOWEEN = 'halloween';

    /** @var Config */
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function isMap(string $map): bool
    {
        return in_array($map, $this->getMaps(false), true);
    }

    /**
     * @return array<int, string>
     */
    public function getMaps(bool $onlyEnabled = true): array
    {
        $maps = array_keys((array)$this->config->get('arenas'));

        if ($onlyEnabled) {
            return array_filter($maps, function ($arena) {
                return (bool)$this->config->getNested('arenas.' . $arena . '.enabled', true);
            });
        }

        return $maps;
    }

    public function setTime(string $map, string $time): void
    {
        if (is_numeric($time)) {
            $intTime = (int)$time;
        } else {
            $intTime = match ($time) {
                'noon' => World::TIME_NOON,
                'sunset' => World::TIME_SUNSET,
                'night' => World::TIME_NIGHT,
                'midnight' => World::TIME_MIDNIGHT,
                'sunrise' => World::TIME_SUNRISE,
                default => World::TIME_DAY
            };
        }
        $this->config->setNested('arenas.' . $map . '.time', $intTime);
        $this->config->save();
    }

    /**
     * @param Arena $arena
     * @return int
     */
    public function getTime(Arena $arena): int
    {
        $time = $this->config->getNested('arenas.' . $arena->getMapName() . '.time');
        if (is_numeric($time)) {
            return (int)$time;
        }
        if ($this->getTag($arena->getMapName()) === ArenaConfig::TAG_HALLOWEEN) {
            return World::TIME_MIDNIGHT;
        }
        return World::TIME_DAY;
    }

    /**
     * @param string $map
     * @param string $tag
     * @return string
     */
    public function setTag(string $map, string $tag): string
    {
        $this->config->setNested('arenas.' . $map . '.tag', $tag);
        $this->config->save();

        return $tag;
    }

    /**
     * @return string
     */
    public function getTag(string $map): string
    {
        $tag = $this->config->getNested('arenas.' . $map . '.tag', '');
        if (is_string($tag)) {
            return $tag;
        }

        return '';
    }

    /**
     * @param Arena $arena
     * @return string|null
     */
    public function getCredits(Arena $arena): ?string
    {
        $credits = $this->config->getNested('arenas.' . $arena->getMapName() . '.credits');
        if (is_string($credits)) {
            return $credits;
        }
        return null;
    }
}