<?php

declare(strict_types=1);


namespace bridges\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const TB_KILLS = 10;
    public const TB_DEATHS = 11;
    public const TB_WINS = 12;
    public const TB_LOSSES = 13;
    public const TB_GOALS = 14;
    public const TB_ARROW_SHOT = 15;
    public const TB_MELEE_HITS = 16;
    public const TB_KILL_ASSISTS = 22;

    public const TB_MODE_KILLS = 17;
    public const TB_MODE_DEATHS = 18;
    public const TB_MODE_WINS = 19;
    public const TB_MODE_LOSSES = 20;
    public const TB_MODE_GOALS = 21;
    public const TB_MODE_KILL_ASSISTS = 23;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::TB_KILLS, 'tb_kills');
        $this->registerStat(self::TB_DEATHS, 'tb_deaths');
        $this->registerStat(self::TB_WINS, 'tb_wins');
        $this->registerStat(self::TB_LOSSES, 'tb_losses');
        $this->registerStat(self::TB_GOALS, 'tb_goals');
        $this->registerStat(self::TB_ARROW_SHOT, 'tb_arrow_shot');
        $this->registerStat(self::TB_MELEE_HITS, 'tb_melee_hits');
        $this->registerStat(self::TB_KILL_ASSISTS, 'tb_kill_assists');

        $this->registerStat(self::TB_MODE_KILLS, 'tb_*mode*_kills');
        $this->registerStat(self::TB_MODE_DEATHS, 'tb_*mode*_deaths');
        $this->registerStat(self::TB_MODE_WINS, 'tb_*mode*_wins');
        $this->registerStat(self::TB_MODE_LOSSES, 'tb_*mode*_losses');
        $this->registerStat(self::TB_MODE_GOALS, 'tb_*mode*_goals');
        $this->registerStat(self::TB_MODE_KILL_ASSISTS, 'tb_*mode*_kill_assists');
    }
}