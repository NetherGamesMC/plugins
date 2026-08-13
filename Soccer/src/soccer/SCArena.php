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

namespace soccer;

use libminigames\Minigame;
use libminigames\tasks\CountDownTask;
use libminigames\TeamArena;
use soccer\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use soccer\tasks\MatchTimeTask;
use function array_search;
use function array_values;
use function arsort;

class SCArena extends TeamArena
{
    public const MODE_SOCCER = 0;

    private const FIELD_MIDDLE = [279.5, 54, 1319.5];
    private const BALL_SPAWN_OFFSET = [0, 1, 0];

    /** @var SCBall|null */
    private ?SCBall $ball = null;

    public function __construct(Minigame $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);

        $this->listener = new SCArenaListener($this);
        $this->statsData = new StatsData($plugin->getModeName($modeId));

        $this->teams = [
            0 => new SCTeam($this, SCTeam::RED),
            1 => new SCTeam($this, SCTeam::DARK_BLUE)
        ];

        $this->maps = ['SC-1'];
    }

    /**
     * @return Soccer
     */
    public function getPlugin(): Minigame
    {
        /** @var Soccer $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    public function sendStats(): void
    {
        $this->getStatsData()->sendLeaderboard($this, StatsData::SC_GOALS, '§l§aTOP SCORERS');
    }

    public function addGoal(Player $player): void
    {
        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsData::SC_GOALS);

        $this->getScoreboard()->setLine([$player], 3, CustomIcon::TARGET . TextFormat::GREEN . $statsData->getValue($player, StatsData::SC_GOALS));
    }

    public function removeGoal(Player $player): void
    {
        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsData::SC_GOALS, -1);

        $this->getScoreboard()->setLine([$player], 3, CustomIcon::TARGET . TextFormat::GREEN . $statsData->getValue($player, StatsData::SC_GOALS));

    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        if (($goals = $this->getStatsData()->getValue($player, StatsData::SC_GOALS)) > 0) {
            $data[self::DATA_XP][] = [
                $goals . ' Goal' . ($goals > 1 ? 's' : ''),
                $goals * 3
            ];

            $data[self::DATA_CREDITS][] = [
                $goals . ' Goal' . ($goals > 1 ? 's' : ''),
                $goals
            ];
        } elseif ($goals < 0) {
            $this->getStatsData()->addValue($player, StatsData::SC_GOALS, abs($goals));
        }

        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                "Win",
                4
            ];
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function getTeamWithHighestScore(): ?SCTeam
    {
        [$redTeam, $blueTeam] = $this->getTeams();

        if (count($blueTeam->getAlivePlayers()) === 0 && count($redTeam->getAlivePlayers()) > 0) {
            return $redTeam;
        }

        if (count($redTeam->getAlivePlayers()) === 0 && count($blueTeam->getAlivePlayers()) > 0) {
            return $blueTeam;
        }

        if ($redTeam->getScore() > $blueTeam->getScore()) {
            return $redTeam;
        }

        if ($redTeam->getScore() < $blueTeam->getScore()) {
            return $blueTeam;
        }

        return null;
    }

    /**
     * @return SCTeam[]
     */
    public function getTeams(): array
    {
        /** @var SCTeam[] $teams */
        $teams = parent::getTeams();

        return $teams;
    }

    public function bootMinigame(): void
    {
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    public function getTeamSize(): int
    {
        return 4;
    }

    public function getMinimumPlayers(): int
    {
        return 2;
    }

    public function startGame(): void
    {
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center('Soccer'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Pass the ball between teammates.'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Get it down the field to the goal.'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'The team with the most goals at the end wins!'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Cross-teaming with other teams or disadvantaging other team members is not allowed in this game. You will be banned if you attempt or threaten to do so.', true);

        foreach ($this->getTeams() as $team) {
            foreach ($team->getPlayers() as $player) {
                /** @var NGPlayer $player */
                $player->setEnergized();
            }

            $team->teleportToSpawn();

            $this->getScoreboard()->setLines($team->getPlayers(), [
                8 => '',
                7 => CustomIcon::HOURGLASS . TextFormat::GREEN . '5:00',
                6 => '',
                5 => $this->getScore($team),
                4 => '',
                3 => CustomIcon::TARGET . TextFormat::GREEN . '0',
                2 => '',
                1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
            ]);
        }

        $this->broadcastTitle(TextFormat::YELLOW . 'Soccer', TextFormat::GREEN . 'May the best team win.', 0, 40, 20);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
    }

    public function getScore(SCTeam $team): string
    {
        $otherTeam = $this->getOtherTeam($team);

        return $team->getColor() . $team->getScore() . TextFormat::GRAY . ' - ' . $otherTeam->getColor() . $otherTeam->getScore();
    }

    public function getOtherTeam(SCTeam $team): SCTeam
    {
        $teams = $this->getTeams();
        unset($teams[array_search($team, $teams, true)]);

        return array_values($teams)[0];
    }

    public function spawnBall(): void
    {
        $middleField = new Vector3(
            self::FIELD_MIDDLE[0] + self::BALL_SPAWN_OFFSET[0],
            self::FIELD_MIDDLE[1] + self::BALL_SPAWN_OFFSET[1],
            self::FIELD_MIDDLE[2] + self::BALL_SPAWN_OFFSET[2]
        );

        if ($this->ball === null || $this->ball->isClosed()) {
            $this->ball = new SCBall(Location::fromObject($middleField, $this->getWorld()), $this);
            $this->ball->spawnToAll();
        } else {
            $this->ball->setNoClientPredictions();
            $this->ball->teleport($middleField);
            $this->ball->setNoClientPredictions(false);
        }
    }

    /**
     * @return SCTeam[]
     */
    public function getAliveTeams(): array
    {
        /** @var SCTeam[] $aliveTeams */
        $aliveTeams = parent::getAliveTeams();

        return $aliveTeams;
    }
}