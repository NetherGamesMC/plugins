<?php

declare(strict_types=1);


namespace bedwars\utils;


use libminigames\Arena;
use function date;
use function time;

class StatsData extends \libminigames\utils\StatsData
{
    public const BW_KILLS = 10;
    public const BW_DEATHS = 11;
    public const BW_WINS = 12;
    public const BW_BEDS_BROKEN = 13;
    public const BW_FINAL_KILLS = 14;

    public const BW_MODE_KILLS = 15;
    public const BW_MODE_DEATHS = 16;
    public const BW_MODE_WINS = 17;
    public const BW_MODE_BEDS_BROKEN = 18;
    public const BW_MODE_FINAL_KILLS = 19;

    public const BW_DIAMONDS_COLLECTED = 20;
    public const BW_EMERALDS_COLLECTED = 21;
    public const BW_GOLD_COLLECTED = 22;
    public const BW_IRON_COLLECTED = 23;

    public const BW_KILL_ASSISTS = 24;
    public const BW_MODE_KILL_ASSISTS = 25;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::BW_KILLS, 'bw_kills');
        $this->registerStat(self::BW_DEATHS, 'bw_deaths');
        $this->registerStat(self::BW_WINS, 'bw_wins');
        $this->registerStat(self::BW_BEDS_BROKEN, 'bw_beds_broken');
        $this->registerStat(self::BW_FINAL_KILLS, 'bw_final_kills');

        $this->registerStat(self::BW_MODE_KILLS, 'bw_*mode*_kills');
        $this->registerStat(self::BW_MODE_DEATHS, 'bw_*mode*_deaths');
        $this->registerStat(self::BW_MODE_WINS, 'bw_*mode*_wins');
        $this->registerStat(self::BW_MODE_BEDS_BROKEN, 'bw_*mode*_beds_broken');
        $this->registerStat(self::BW_MODE_FINAL_KILLS, 'bw_*mode*_final_kills');

        $this->registerStat(self::BW_DIAMONDS_COLLECTED, 'bw_diamonds_collected');
        $this->registerStat(self::BW_EMERALDS_COLLECTED, 'bw_emeralds_collected');
        $this->registerStat(self::BW_GOLD_COLLECTED, 'bw_gold_collected');
        $this->registerStat(self::BW_IRON_COLLECTED, 'bw_iron_collected');

        $this->registerStat(self::BW_KILL_ASSISTS, 'bw_kill_assists');
        $this->registerStat(self::BW_MODE_KILL_ASSISTS, 'bw_*mode*_kill_assists');
    }

    public function save(Arena $arena): void
    {
        if (date('m-d', time()) === '04-01') {
            return;
        }

        parent::save($arena);
    }
}