<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer\tasks;

use libminigames\Arena;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\PopSound;
use soccer\SCArena;
use soccer\utils\StatsData;
use function abs;
use function date;
use function strtoupper;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public function __construct(Arena $arena)
    {
        parent::__construct($arena);

        $this->timePassed = -10;
    }

    public function gameTick(): void
    {
        $arena = $this->getArena();

        if ($this->timePassed < 0) {
            if ($this->timePassed === -1) {
                foreach ($arena->getPlayers() as $player) {
                    /** @var NGPlayer $player */
                    $player->playSound('note.hat', 1, 0.943874);
                }

                $arena->spawnBall();

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'Match starts in ' . TextFormat::RED . '1 §esecond!');
            } elseif ($this->timePassed >= -3) {
                foreach ($arena->getPlayers() as $player) {
                    /** @var NGPlayer $player */
                    $player->playSound('note.hat', 1, 0.943874);
                }

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'Match starts in ' . TextFormat::RED . abs($this->timePassed) . ' §eseconds!');
            } elseif ($this->timePassed >= -5) {
                foreach ($arena->getPlayers() as $player) {
                    $arena->getWorld()->addSound($player->getLocation(), new PopSound(), [$player]);
                }

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'Match starts in ' . TextFormat::YELLOW . abs($this->timePassed) . ' §eseconds!');
            }
        } elseif ($this->timePassed === 0) {
            foreach ($arena->getPlayers() as $player) {
                $player->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
                $player->setNoClientPredictions(false);
            }
        }

        $this->updateScoreboard();
    }

    /**
     * @return SCArena
     */
    public function getArena(): Arena
    {
        /** @var SCArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function updateScoreboard(): void
    {
        $arena = $this->getArena();
        $players = $arena->getPlayers();
        $scoreboard = $arena->getScoreboard();

        if ($this->timePassed < 0) {
            $scoreboard->setLine($players, 7, CustomIcon::HOURGLASS . TextFormat::GREEN . 'Starts in ' . date('i:s', abs($this->timePassed)));
        } else {
            $scoreboard->setLine($players, 7, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
        }
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

            $statsData = $arena->getStatsData();
            foreach ($bestTeam->getXuids() as $xuid) {
                $statsData->addValue($xuid, StatsData::WINS);
                $statsData->addValue($xuid, StatsData::SC_WINS);
            }
        }
    }

    public function overTimeTick(): void
    {
        $this->assignWinners();
        parent::overTimeTick();
    }

    public function getPlayingTime(): int
    {
        return 5 * 60;
    }
}