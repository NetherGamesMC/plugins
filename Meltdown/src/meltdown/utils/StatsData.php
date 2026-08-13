<?php

namespace meltdown\utils;

class StatsData extends \libminigames\utils\StatsData
{

    public const MD_KILLS = 130;
    public const MD_DEATHS = 131;
    public const MD_WINS = 132;
    public const MD_LOSSES = 133;

    public const MD_POWERUPS_COLLECTED = 134;

    /**
     * @param string $mode
     * @param string[] $types
     */
    public function __construct(string $mode, array $types = [])
    {
        parent::__construct($mode, $types);

        $this->registerStat(self::MD_KILLS, 'md_kills');
        $this->registerStat(self::MD_DEATHS, 'md_deaths');
        $this->registerStat(self::MD_WINS, 'md_wins');
        $this->registerStat(self::MD_LOSSES, 'md_losses');

        $this->registerStat(self::MD_POWERUPS_COLLECTED, 'md_powerups_collected');
    }

}
