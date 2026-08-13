<?php

declare(strict_types=1);


namespace murdermystery\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const MM_KILLS = 10;
    public const MM_DEATHS = 11;
    public const MM_WINS = 12;

    public const MM_MODE_KILLS = 13;
    public const MM_MODE_DEATHS = 14;
    public const MM_MODE_WINS = 15;

    public const MM_BOW_KILLS = 16;
    public const MM_KNIFE_KILLS = 17;
    public const MM_THROW_KNIFE_KILLS = 18;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::MM_KILLS, 'mm_kills');
        $this->registerStat(self::MM_DEATHS, 'mm_deaths');
        $this->registerStat(self::MM_WINS, 'mm_wins');
        $this->registerStat(self::MM_MODE_KILLS, 'mm_*mode*_kills');
        $this->registerStat(self::MM_MODE_DEATHS, 'mm_*mode*_deaths');
        $this->registerStat(self::MM_MODE_WINS, 'mm_*mode*_wins');
        $this->registerStat(self::MM_BOW_KILLS, 'mm_bow_kills');
        $this->registerStat(self::MM_KNIFE_KILLS, 'mm_knife_kills');
        $this->registerStat(self::MM_THROW_KNIFE_KILLS, 'mm_throw_knife_kills');
    }
}