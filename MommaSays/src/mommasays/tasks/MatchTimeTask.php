<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\tasks;

use libminigames\Arena;
use mommasays\games\Game;
use mommasays\games\GameKeepMove;
use mommasays\games\GameNoMove;
use mommasays\MSArena;
use mommasays\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function microtime;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public const GAMES_PLAYING = 15;
    public const PAUSE_TIME = 3;
    public const GAME_TIME = 15;
    public const STATE_MINIGAME = 0x01;
    public const STATE_PAUSE = 0x02;
    public const STATE_START = 0x03;

    /** @var int */
    public int $timer = self::GAME_TIME;
    /** @var int */
    public int $mode = self::STATE_START;
    /** @var int */
    private int $played = 0;

    public function gameTick(): void
    {
        $arena = $this->getArena();

        if ($arena->isRunning()) {
            $inGame = $this->mode === self::STATE_MINIGAME;
            $isStarting = $this->mode === self::STATE_START;

            if ($isStarting) {
                if (self::GAME_TIME - $this->timer > 3) {
                    $this->timer = self::GAME_TIME;
                    $this->next();
                }
            } else {
                $currentGame = $arena->getCurrentGame();

                if ($this->timer > 0) {
                    if ($currentGame instanceof GameNoMove) {
                        /** @var NGPlayer $player */
                        foreach ($arena->getPlayers() as $player) {
                            $lastMoveTime = $player->getLastMoveTime();
                            if (round($lastMoveTime, 10) - round(microtime(true), 10) <= (1 / 20)) {
                                $currentGame->onPlayerMove($player);
                            }
                        }
                    } elseif ($currentGame instanceof GameKeepMove) {
                        $currentGame->tickMovementCheck();
                    }

                    $finished = 0;
                    foreach ($arena->getPlayers() as $player) {
                        if ($currentGame->isWinner($player->getName()) || $currentGame->isLoser($player->getName())) {
                            $finished++;
                        }
                    }
                    if ($finished === count($arena->getPlayers())) {
                        $this->timer = 0;
                    }

                    $arena->broadcastTip(TextFormat::GOLD . $currentGame->getMessage());
                } elseif ($inGame) { // Counter is at or below 0, time's up
                    $currentGame->resetArena();
                    $currentGame->finishGame();

                    $title = false;
                    if (random_int(1, 5) === 1 && $currentGame->getFirst() !== null) {
                        $arena->broadcastTitle(' ', $currentGame->getFirst() . TextFormat::RED . ' got 1st place!', 0, 80, 20);
                    } else {
                        $title = true;
                    }

                    foreach ($arena->getAlivePlayers() as $player) {
                        if ($currentGame->isWinner($player->getName())) {
                            if ($title) {
                                $player->sendTitle(' ', TextFormat::GREEN . 'You successfully finished the game!', 0, 80, 20);
                            }
                            $player->sendMessage(TextFormat::GREEN . 'You successfully finished the game. ' . TextFormat::GOLD . $arena->getGamesWon($player) . TextFormat::YELLOW . '/' . self::GAMES_PLAYING . ' succeeded.');
                        } else {
                            if ($title) {
                                $player->sendTitle(' ', TextFormat::RED . 'You failed the game!', 0, 80, 20);
                            }
                            $player->sendMessage(TextFormat::RED . 'You failed the game. ' . TextFormat::GOLD . $arena->getGamesWon($player) . TextFormat::YELLOW . '/' . self::GAMES_PLAYING . ' succeeded.');
                        }
                    }

                    if ($this->played < self::GAMES_PLAYING) {
                        $arena->broadcastMessage(TextFormat::GREEN . 'Game ended! Next game is starting in 3 seconds.');
                    }

                    $topPlayers = array_keys(array_slice($arena->getLeaderboard(), 0, 3));
                    $leaderboard = array_filter(array_values($arena->getLeaderboard()));
                    foreach ($arena->getPlayers() as $player) {
                        $arena->getScoreboard()->removePlayer($player);
                        $arena->getScoreboard()->addPlayer($player);
                        if (!in_array($player->getName(), $topPlayers, true)) {
                            if (isset($leaderboard[2])) {
                                $arena->getScoreboard()->setLines([$player], [
                                    10 => '',
                                    9 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                    8 => CustomIcon::TROPHY_SILVER . $leaderboard[1][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[1][1],
                                    7 => CustomIcon::TROPHY_BRONZE . $leaderboard[2][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[2][1],
                                    6 => '. . .',
                                    5 => $player->getNameTag() . TextFormat::GRAY . TextFormat::GREEN . $arena->getPoints($player),
                                    4 => '',
                                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                    2 => '',
                                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                                ]);
                            } elseif (isset($leaderboard[1])) {
                                $arena->getScoreboard()->setLines([$player], [
                                    9 => '',
                                    8 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                    7 => CustomIcon::TROPHY_SILVER . $leaderboard[1][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[1][1],
                                    6 => '. . .',
                                    5 => $player->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . $arena->getPoints($player),
                                    4 => '',
                                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                    2 => '',
                                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                                ]);
                            } elseif (isset($leaderboard[0])) {
                                $arena->getScoreboard()->setLines([$player], [
                                    8 => '',
                                    7 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                    6 => '. . .',
                                    5 => $player->getNameTag() . TextFormat::GRAY . TextFormat::GREEN . $arena->getPoints($player),
                                    4 => '',
                                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                    2 => '',
                                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                                ]);
                            }
                        } elseif (isset($leaderboard[2])) {
                            $arena->getScoreboard()->setLines([$player], [
                                8 => '',
                                7 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                6 => CustomIcon::TROPHY_SILVER . $leaderboard[1][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[1][1],
                                5 => CustomIcon::TROPHY_BRONZE . $leaderboard[2][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[2][1],
                                4 => '',
                                3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                2 => '',
                                1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                            ]);
                        } elseif (isset($leaderboard[1])) {
                            $arena->getScoreboard()->setLines([$player], [
                                7 => '',
                                6 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                5 => CustomIcon::TROPHY_SILVER . $leaderboard[1][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[1][1],
                                4 => '',
                                3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                2 => '',
                                1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                            ]);
                        } elseif (isset($leaderboard[0])) {
                            $arena->getScoreboard()->setLines([$player], [
                                6 => '',
                                5 => CustomIcon::TROPHY_GOLD . $leaderboard[0][0] . TextFormat::GRAY . ' ' . TextFormat::GREEN . $leaderboard[0][1],
                                4 => '',
                                3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING,
                                2 => '',
                                1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                            ]);
                        }
                    }

                    if ($this->played >= self::GAMES_PLAYING) {
                        // maximal amount of games reached, ending the game...
                        $this->finishArena();

                        $pos = new Vector3(Game::ARENA_SPAWN_POINT[0], Game::ARENA_SPAWN_POINT[1], Game::ARENA_SPAWN_POINT[2]);
                        foreach ($arena->getPlayers() as $player) {
                            $player->teleport($pos);
                        }

                        return;
                    }

                    $this->timer = self::PAUSE_TIME;
                    $this->mode = self::STATE_PAUSE;
                } else {
                    $this->next();
                }
            }

            $this->timer--;
        }
    }

    /**
     * @return MSArena
     */
    public function getArena(): Arena
    {
        /** @var MSArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function next(): void
    {
        $arena = $this->getArena();

        $oldGame = $arena->getCurrentGame();
        $teleport = false;

        if ($oldGame !== null) {
            $arena->addToPlayedGame($oldGame);
            $teleport = $oldGame->isUsingCages();
        }
        if ($this->played === 0) {
            $teleport = true;
        }

        $newGame = $arena->getNotPlayedMinigame($teleport);
        $arena->setCurrentGame($newGame);

        if ($this->played === 0) {
            $arena->getCurrentGame()->setupArena();
            $this->announceNewGameAndContinue($arena->getCurrentGame());
        } else {
            $newGame->setupArena();
            $this->announceNewGameAndContinue($newGame);
        }
    }

    /**
     * @param Game $game
     * This method was added to avoid code duplication
     * Because the first game needs to have the setup executed asynchronous & delayed,
     * to avoid Chunk-Based Race-Conditions where the removal of the waiting lobby overwrites the chunk operations of the
     * first Game
     */
    public function announceNewGameAndContinue(Game $game): void
    {
        $this->mode = self::STATE_MINIGAME;
        $this->timer = self::GAME_TIME;

        $this->getArena()->broadcastTitle(' ', TextFormat::YELLOW . $game->getMessage());
        $this->getArena()->broadcastTip(TextFormat::GOLD . $game->getMessage());

        $this->played++;

        $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 3, CustomIcon::GAMEMODE . ' ' . TextFormat::GREEN . $this->played . '/' . self::GAMES_PLAYING);
    }

    public function finishArena(): void
    {
        $arena = $this->getArena();

        $highestPoint = 0;
        $winners = [];
        $winnerNames = [];

        foreach ($arena->getLeaderboard() as $player => $data) {
            if ($highestPoint <= $data[1]) {
                $highestPoint = $data[1];

                if (($p = $arena->getPlugin()->getServer()->getPlayerExact($player)) !== null) {
                    $winners[] = $p;
                    $winnerNames[] = $data[0];
                }
            }
        }

        if (count($winners) > 0) {
            $lastWinner = array_pop($winnerNames);
            $subtitle = ($winnerNames ? implode(TextFormat::RED . ', ', $winnerNames) . TextFormat::RED . ' and ' . $lastWinner : $lastWinner) . TextFormat::RED . ' won!';

            $statsData = $arena->getStatsData();
            foreach ($winners as $winner) {
                $statsData->addValue($winner, StatsData::WINS);
                $statsData->addValue($winner, StatsData::MS_WINS);
            }
        } else {
            $subtitle = TextFormat::RED . 'No one won!';
        }

        parent::finishArena();

        foreach ($arena->getAlivePlayers() as $player) {
            $player->teleport(new Vector3(Game::ARENA_SPAWN_POINT[0], Game::ARENA_SPAWN_POINT[1], Game::ARENA_SPAWN_POINT[2]));

            if ($arena->isWinner($player)) {
                $player->sendTitle(TextFormat::GOLD . 'Game over!', TextFormat::GREEN . 'You won!', 0, 100, 20);
            } else {
                $player->sendTitle(TextFormat::GOLD . 'Game over!', $subtitle, 0, 100, 20);
            }
        }
    }

    public function getPlayingTime(): int
    {
        return ((self::PAUSE_TIME + self::GAME_TIME) * self::GAMES_PLAYING) * 60; // Amount of minutes we will totally play, this might be inaccurate due to integer
    }
}