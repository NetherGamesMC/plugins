<?php

declare(strict_types=1);


namespace soccer\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const SC_WINS = 10;
    public const SC_GOALS = 11;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::SC_WINS, 'sc_wins');
        $this->registerStat(self::SC_GOALS, 'sc_goals');
    }
}