<?php

declare(strict_types=1);


namespace mommasays\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const MS_WINS = 10;
    public const MS_SUCCESSES = 11;
    public const MS_FAILS = 12;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::MS_WINS, 'ms_wins');
        $this->registerStat(self::MS_SUCCESSES, 'ms_successes');
        $this->registerStat(self::MS_FAILS, 'ms_fails');
    }
}