<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
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

namespace duels\tasks;

use duels\DuelsArena;
use duels\DuelsTeam;
use duels\utils\StatsData;
use libminigames\utils\StatsData as StatsDataAlias;
use libminigames\Arena;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\TextFormat;
use function date;
use function round;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public function getPlayingTime(): int
    {
        return 5 * 60;
    }

    public function finishArena(): void
    {
        $arena = $this->getArena();
        /** @var DuelsTeam|null $aliveTeam */
        $aliveTeam = $arena->getAliveTeams()[0] ?? null;

        if ($aliveTeam !== null) {
            foreach ($aliveTeam->getPlayers() as $player) {
                if ($this->getArena()->isSoloGame()) {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last player standing!', 0, 100, 20);
                } else {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last team standing!', 0, 100, 20);
                }
            }
        }

        $statsData = $arena->getStatsData();
        foreach ($arena->getTeams() as $team) {
            foreach ($team->getXuids() as $xuid) {
                if ($team === $aliveTeam) {
                    $statsData->addValue($xuid, StatsDataAlias::WINS);
                    $statsData->addValue($xuid, StatsData::DUELS_WINS);
                } else {
                    $statsData->addValue($xuid, StatsDataAlias::LOSSES);
                    $statsData->addValue($xuid, StatsData::DUELS_LOSSES);
                }
            }
        }

        parent::finishArena();
    }

    /**
     * @return DuelsArena
     */
    public function getArena(): Arena
    {
        /** @var DuelsArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function overTimeTick(): void
    {
        if (!$this->getArena()->getGameSettings()->hasEndlessGame()) {
            $this->getArena()->broadcastTitle(TextFormat::RED . 'YOU LOSE!', TextFormat::GOLD . ' You ran out of time!');

            parent::overTimeTick();
        } else {
            $arena = $this->getArena();
            $arena->setStatus(Arena::STATUS_FINISHING);
        }
    }

    public function gameTick(): void
    {
        $this->updateScoreboard();

        $arena = $this->getArena();
        if ($this->timePassed === 2 && $arena->getType() === DuelsArena::TYPE_SUMO) {
            $arena->getPlugin()->getScheduler()->scheduleRepeatingTask(new SumoTask($arena), 5);
        }
    }

    public function updateScoreboard(): void
    {
        if ($this->getArena()->isSoloGame()) {
            foreach ($this->getArena()->getAliveTeams() as $aliveTeam) {
                $opponent = ['UNKNOWN', '0'];
                foreach ($this->getArena()->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getPlayers() as $player) {
                        $opponent = [$this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $player->getHealth()];
                    }
                    break;
                }
                $this->getArena()->getScoreboard()->setLine($aliveTeam->getPlayers(), 5, TextFormat::AQUA . $opponent[0] . ' ' . TextFormat::GREEN . round((int)$opponent[1] / 2, 1) . CustomIcon::HEART);
            }
            $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 8, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
        } else {
            foreach ($this->getArena()->getAliveTeams() as $aliveTeam) {
                $opponent = [['UNKNOWN', '0'], ['UNKNOWN', '0']];
                foreach ($this->getArena()->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getPlayers() as $player) {
                        $opponent[$key++] = [$this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $player->getHealth()];
                    }
                    break;
                }
                $this->getArena()->getScoreboard()->setLine($aliveTeam->getPlayers(), 6, TextFormat::AQUA . $opponent[0][0] . ' ' . TextFormat::GREEN . round((int)$opponent[0][1] / 2, 1) . CustomIcon::HEART);
                $this->getArena()->getScoreboard()->setLine($aliveTeam->getPlayers(), 5, TextFormat::AQUA . $opponent[1][0] . ' ' . TextFormat::GREEN . round((int)$opponent[1][1] / 2, 1) . CustomIcon::HEART);
            }
            $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 9, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
        }
    }
}