<?php

declare(strict_types=1);


namespace survivalgames\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const SG_KILLS = 10;
    public const SG_DEATHS = 11;
    public const SG_WINS = 12;

    public function __construct(string $mode, array $types)
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::SG_KILLS, 'sg_kills');
        $this->registerStat(self::SG_DEATHS, 'sg_deaths');
        $this->registerStat(self::SG_WINS, 'sg_wins');
    }
}