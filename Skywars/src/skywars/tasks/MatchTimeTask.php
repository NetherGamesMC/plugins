<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace skywars\tasks;

use libminigames\TeamArena;
use libminigames\utils\StatsData as StatsDataAlias;
use pocketmine\utils\TextFormat;
use skywars\handler\DuelsHandler;
use skywars\handler\NormalHandler;
use skywars\SWArena;
use skywars\utils\StatsData;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    /** @var int */
    public const CAGE_TIME = 10;

    public function __construct(SWArena $arena)
    {
        parent::__construct($arena);

        if ($this->getArena()->isDuelsGame()) {
            $this->timePassed = -5;
        } else {
            $this->timePassed = -self::CAGE_TIME;
        }
    }

    /**
     * @return SWArena
     */
    public function getArena(): TeamArena
    {
        /** @var SWArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function getPlayingTime(): int
    {
        if ($this->getArena()->isDuelsGame()) {
            return 6 * 60;
        }

        return 15 * 60;
    }

    public function overTimeTick(): void
    {
        $this->getArena()->broadcastTitle(TextFormat::RED . 'YOU LOSE!', TextFormat::GOLD . ' You ran out of time!');

        parent::overTimeTick();
    }

    public function gameTick(): void
    {
        $this->getArena()->getHandler()->gameTick($this->time, $this->timePassed);
    }

    public function finishArena(): void
    {
        $arena = $this->getArena();
        /** @var SWTeam|null $aliveTeam */
        $aliveTeam = $arena->getAliveTeams()[0] ?? null;

        if ($aliveTeam !== null) {
            $players = $aliveTeam->getPlayers();

            foreach ($players as $player) {
                if ($this->getArena()->isSoloGame()) {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last player standing!', 0, 100, 20);
                } else {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last team standing!', 0, 100, 20);
                }
            }
        }

        $statsData = $arena->getStatsData();
        $handler = $arena->getHandler();
        foreach ($arena->getTeams() as $team) {
            foreach ($team->getXuids() as $xuid) {
                if ($team === $aliveTeam) {
                    $statsData->addValue($xuid, StatsDataAlias::WINS);

                    if ($handler instanceof NormalHandler) {
                        $statsData->addValue($xuid, StatsData::SW_WINS);
                        $statsData->addValue($xuid, StatsData::SW_MODE_WINS);
                    }
                    if ($handler instanceof DuelsHandler) {
                        $statsData->addValue($xuid, StatsData::DUELS_WINS);
                    }
                } else {
                    $statsData->addValue($xuid, StatsDataAlias::LOSSES);

                    if ($handler instanceof NormalHandler) {
                        $statsData->addValue($xuid, StatsData::SW_LOSSES);
                        $statsData->addValue($xuid, StatsData::SW_MODE_LOSSES);
                    }
                    if ($handler instanceof DuelsHandler) {
                        $statsData->addValue($xuid, StatsData::DUELS_LOSSES);
                    }
                }
            }
        }

        parent::finishArena();
    }
}