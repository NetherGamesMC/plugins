<?php

declare(strict_types=1);


namespace conquests\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const CQ_KILLS = 10;
    public const CQ_DEATHS = 11;
    public const CQ_WINS = 12;

    public const CQ_FLAGS_COLLECTED = 13;
    public const CQ_FLAGS_CAPTURED = 14;
    public const CQ_FLAGS_RETURNED = 15;

    public const CQ_DIAMONDS_COLLECTED = 16;
    public const CQ_EMERALDS_COLLECTED = 17;
    public const CQ_GOLD_COLLECTED = 18;
    public const CQ_IRON_COLLECTED = 19;

    public const CQ_KILL_ASSISTS = 20;

    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::CQ_KILLS, 'cq_kills');
        $this->registerStat(self::CQ_DEATHS, 'cq_deaths');
        $this->registerStat(self::CQ_WINS, 'cq_wins');

        $this->registerStat(self::CQ_FLAGS_COLLECTED, 'cq_flags_collected');
        $this->registerStat(self::CQ_FLAGS_CAPTURED, 'cq_flags_captured');
        $this->registerStat(self::CQ_FLAGS_RETURNED, 'cq_flags_returned');

        $this->registerStat(self::CQ_DIAMONDS_COLLECTED, 'cq_diamonds_collected');
        $this->registerStat(self::CQ_EMERALDS_COLLECTED, 'cq_emeralds_collected');
        $this->registerStat(self::CQ_GOLD_COLLECTED, 'cq_gold_collected');
        $this->registerStat(self::CQ_IRON_COLLECTED, 'cq_iron_collected');

        $this->registerStat(self::CQ_KILL_ASSISTS, 'cq_kill_assists');
    }
}