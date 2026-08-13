<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges\tasks;

use bridges\BridgeArena;
use bridges\utils\StatsData;
use libminigames\Arena;
use libminigames\utils\StatsData as StatsDataAlias;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\TextFormat;
use function date;
use function strtoupper;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public function getPlayingTime(): int
    {
        return 15 * 60;
    }

    public function gameTick(): void
    {
        $arena = $this->getArena();

        if ($arena->phase === BridgeArena::PHASE_FINISH) {
            $this->finishArena();
        } else {
            if ($arena->phase === BridgeArena::PHASE_RESTART) {
                $arena->time--;

                if ($arena->time > 0) {
                    foreach ($this->getArena()->getAliveTeams() as $aliveTeam) {
                        $spawn = $aliveTeam->getSpawnPosition();

                        foreach ($aliveTeam->getAlivePlayers() as $player) {
                            if ($player->getLocation()->distance($spawn) > 5) {
                                $player->teleport($spawn);
                            }
                        }
                    }

                    $arena->broadcastTitle('', TextFormat::GRAY . 'Cages open in ' . TextFormat::GREEN . $arena->time . 's' . TextFormat::GRAY . '...');
                } elseif ($arena->time === 0) {
                    $arena->broadcastTitle(' ', TextFormat::GREEN . 'Fight!', 0, 20, 20);
                    $arena->phase = BridgeArena::PHASE_RUN;
                    $arena->time = 10;

                    $arena->removeCages();

                    foreach ($this->getArena()->getTeams() as $team) {
                        $team->releasePlayers();
                    }
                }
            }

            $this->updateScoreboard();
        }
    }

    /**
     * @return BridgeArena
     */
    public function getArena(): Arena
    {
        /** @var BridgeArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function finishArena(): void
    {
        $this->assignWinners();
        parent::finishArena();
    }

    public function assignWinners(): void
    {
        $arena = $this->getArena();

        $bestTeam = $arena->getTeamWithHighestScore();
        if ($bestTeam === null) {
            $arena->broadcastTitle(TextFormat::BOLD . TextFormat::YELLOW . 'DRAW!', TextFormat::YELLOW . 'Reached time limit!', 0, 100, 20);
        } else {
            $otherTeam = $arena->getOtherTeam($bestTeam);
            $arena->broadcastTitle(TextFormat::BOLD . $bestTeam->getColor() . strtoupper($bestTeam->getName()) . ' WINS!', TextFormat::BOLD . $bestTeam->getColor() . $bestTeam->getScore() . TextFormat::GRAY . ' - ' . $otherTeam->getColor() . $otherTeam->getScore(), 0, 100, 20);
        }

        $statsData = $arena->getStatsData();
        foreach ($arena->getTeams() as $team) {
            foreach ($team->getXuids() as $xuid) {
                if ($team === $bestTeam) {
                    $statsData->addValue($xuid, StatsDataAlias::WINS);
                    $statsData->addValue($xuid, StatsData::TB_WINS);
                    $statsData->addValue($xuid, StatsData::TB_MODE_WINS);
                } else {
                    $statsData->addValue($xuid, StatsDataAlias::LOSSES);
                    $statsData->addValue($xuid, StatsData::TB_LOSSES);
                    $statsData->addValue($xuid, StatsData::TB_MODE_LOSSES);
                }
            }
        }
    }

    public function updateScoreboard(): void
    {
        $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 12, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
    }

    public function overTimeTick(): void
    {
        if (!$this->getArena()->getGameSettings()->hasEndlessGame()) {
            $this->getArena()->setRanOutOfTime(true);

            $this->assignWinners();
            parent::overTimeTick();
        } else {
            // just update the scoreboard to continue displaying time
            $this->updateScoreboard();
        }
    }
}