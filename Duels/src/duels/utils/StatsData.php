<?php

declare(strict_types=1);


namespace duels\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const DUELS_KILLS = 10;
    public const DUELS_DEATHS = 11;
    public const DUELS_WINS = 12;
    public const DUELS_LOSSES = 13;
    public const DUELS_KILL_ASSISTS = 14;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::DUELS_KILLS, 'duels_kills');
        $this->registerStat(self::DUELS_DEATHS, 'duels_deaths');
        $this->registerStat(self::DUELS_WINS, 'duels_wins');
        $this->registerStat(self::DUELS_LOSSES, 'duels_losses');
        $this->registerStat(self::DUELS_KILL_ASSISTS, 'duels_kill_assists');
    }
}