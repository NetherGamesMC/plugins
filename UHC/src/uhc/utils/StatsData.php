<?php

declare(strict_types=1);


namespace uhc\utils;


class StatsData extends \libminigames\utils\StatsData
{
    public const UHC_KILLS = 10;
    public const UHC_DEATHS = 11;
    public const UHC_WINS = 12;

    public const UHC_IRON_MINED = 13;
    public const UHC_GOLD_MINED = 14;
    public const UHC_LAPIS_MINED = 15;
    public const UHC_DIAMOND_MINED = 16;

    public function __construct(string $mode)
    {
        parent::__construct($mode, []);

        $this->registerStat(self::UHC_KILLS, 'uhc_kills');
        $this->registerStat(self::UHC_DEATHS, 'uhc_deaths');
        $this->registerStat(self::UHC_WINS, 'uhc_wins');

        $this->registerStat(self::UHC_IRON_MINED, 'uhc_iron_mined');
        $this->registerStat(self::UHC_GOLD_MINED, 'uhc_gold_mined');
        $this->registerStat(self::UHC_LAPIS_MINED, 'uhc_lapis_mined');
        $this->registerStat(self::UHC_DIAMOND_MINED, 'uhc_diamond_mined');
    }
}