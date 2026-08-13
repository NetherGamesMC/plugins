<?php

declare(strict_types=1);


namespace skywars\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const SW_KILLS = 10;
    public const SW_DEATHS = 11;
    public const SW_WINS = 12;
    public const SW_LOSSES = 13;

    public const SW_MODE_KILLS = 14;
    public const SW_MODE_DEATHS = 15;
    public const SW_MODE_TYPE_KILLS = 16;
    public const SW_MODE_TYPE_DEATHS = 17;
    public const SW_MODE_WINS = 18;
    public const SW_MODE_LOSSES = 19;

    public const SW_BLOCKS_BROKEN = 20;
    public const SW_BLOCKS_PLACED = 21;
    public const SW_EGGS_THROWN = 22;
    public const SW_EPEARLS_THROWN = 23;

    public const DUELS_KILLS = 24;
    public const DUELS_DEATHS = 25;
    public const DUELS_WINS = 26;
    public const DUELS_LOSSES = 27;
    public const DUELS_KILL_ASSISTS = 28;
    public const DUELS_MODE_KILL_ASSISTS = 29;

    public const SW_KILL_ASSISTS = 30;
    public const SW_MODE_KILL_ASSISTS = 31;

    public function __construct(string $mode, array $types)
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::SW_KILLS, 'sw_kills');
        $this->registerStat(self::SW_DEATHS, 'sw_deaths');
        $this->registerStat(self::SW_WINS, 'sw_wins');
        $this->registerStat(self::SW_LOSSES, 'sw_losses');

        $this->registerStat(self::SW_MODE_KILLS, 'sw_*mode*_kills');
        $this->registerStat(self::SW_MODE_DEATHS, 'sw_*mode*_deaths');
        $this->registerStat(self::SW_MODE_TYPE_KILLS, 'sw_*mode*_*type*_kills');
        $this->registerStat(self::SW_MODE_TYPE_DEATHS, 'sw_*mode*_*type*_deaths');
        $this->registerStat(self::SW_MODE_WINS, 'sw_*mode*_wins');
        $this->registerStat(self::SW_MODE_LOSSES, 'sw_*mode*_losses');

        $this->registerStat(self::SW_BLOCKS_BROKEN, 'sw_blocks_broken');
        $this->registerStat(self::SW_BLOCKS_PLACED, 'sw_blocks_placed');
        $this->registerStat(self::SW_EGGS_THROWN, 'sw_eggs_thrown');
        $this->registerStat(self::SW_EPEARLS_THROWN, 'sw_epearls_thrown');

        $this->registerStat(self::DUELS_KILLS, 'duels_kills');
        $this->registerStat(self::DUELS_DEATHS, 'duels_deaths');
        $this->registerStat(self::DUELS_WINS, 'duels_wins');
        $this->registerStat(self::DUELS_LOSSES, 'duels_losses');
        $this->registerStat(self::DUELS_KILL_ASSISTS, 'duels_kill_assists');
        $this->registerStat(self::DUELS_MODE_KILL_ASSISTS, 'duels_*mode*_kill_assists');

        $this->registerStat(self::SW_KILL_ASSISTS, 'sw_kill_assists');
        $this->registerStat(self::SW_MODE_KILL_ASSISTS, 'sw_*mode*_kill_assists');
    }
}