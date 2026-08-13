<?php

declare(strict_types=1);

namespace skywars\handler;

use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\utils\TextFormat;

class DuelsHandler extends BaseHandler
{
    public function startGame(): void
    {
        parent::startGame();

        $arena = $this->arena;
        $essentials = $this->plugin->getEssentials();
        $playerData = $essentials->getPlayerData();
        $playerManager = $essentials->getPlayerManager();

        $arena->broadcastMessage(TextUtils::center('Skywars Duel'), true);
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Eliminate your opponents!'), true);
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextUtils::center(TextFormat::GRAY . 'This Duels mode will not count towards Skywars stats'), true);
        if (($credits = $this->plugin->getArenaConfig()->getCredits($arena)) !== null) {
            $arena->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . $arena->getMapDisplayName() . ", by " . $credits), true);
        }
        $arena->broadcastMessage('', true);
        if ($arena->isSoloGame()) {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = '';
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getAlivePlayers() as $player) {
                        $opponent = $playerManager->getPlayerColouredName($player);
                    }
                    break;
                }
                foreach ($aliveTeam->getPlayers() as $player) {
                    $player->sendMessage(TextUtils::center(TextFormat::WHITE . TextFormat::BOLD . 'Opponent: ' . TextFormat::RESET . $opponent));
                }
            }
        } else {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = ['', ''];
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getAlivePlayers() as $player) {
                        $opponent[$key++] = $playerManager->getPlayerColouredName($player);
                    }
                    break;
                }
                foreach ($aliveTeam->getPlayers() as $player) {
                    $player->sendMessage(TextUtils::center(TextFormat::WHITE . TextFormat::BOLD . 'Opponents: ' . TextFormat::RESET . $opponent[0] . TextFormat::WHITE . ' & ' . TextFormat::RESET . $opponent[1]));
                }
            }
        }

        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        if ($arena->isSoloGame()) {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = ['UNKNOWN', '0'];
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent = [$playerManager->getPlayerName($player), $health];
                    }
                    break;
                }

                foreach ($aliveTeam->getPlayers() as $player) {
                    $arena->getScoreboard()->setLines([$player], [
                            9 => '',
                            8 => CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . '5:00',
                            7 => '',
                            6 => CustomIcon::PLAYERS_TINY,
                            5 => TextFormat::AQUA . $opponent[0] . ' ' . TextFormat::GREEN . round((int)$opponent[1] / 2, 1) . TextFormat::RED . CustomIcon::HEART,
                            4 => '',
                            3 => CustomIcon::GAMEMODE . ' ' . TextFormat::GREEN . $arena->getTypeName(),
                            2 => '',
                            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                        ]
                    );
                }
            }
        } else {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = [['UNKNOWN', '0'], ['UNKNOWN', '0']];
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent[$key++] = [$playerManager->getPlayerName($player), $health];
                    }
                    break;
                }

                foreach ($aliveTeam->getPlayers() as $player) {
                    $arena->getScoreboard()->setLines([$player], [
                            10 => '',
                            9 => CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . '5:00',
                            8 => '',
                            7 => CustomIcon::PLAYERS_TINY,
                            6 => TextFormat::AQUA . $opponent[0][0] . ' ' . TextFormat::GREEN . round((int)$opponent[0][1] / 2, 1) . TextFormat::RED . CustomIcon::HEART,
                            5 => TextFormat::AQUA . $opponent[1][0] . ' ' . TextFormat::GREEN . round((int)$opponent[1][1] / 2, 1) . TextFormat::RED . CustomIcon::HEART,
                            4 => '',
                            3 => CustomIcon::GAMEMODE . ' ' . TextFormat::GREEN . $arena->getTypeName(),
                            2 => '',
                            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                        ]
                    );
                }
            }
        }
    }

    public function updateScoreboard(int $time, int $timePassed): void
    {
        $arena = $this->arena;
        $scoreboard = $arena->getScoreboard();

        if ($arena->isSoloGame()) {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = ['UNKNOWN', '0'];
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent = [$arena->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $health];
                    }
                    break;
                }
                $scoreboard->setLine($aliveTeam->getPlayers(), 5, TextFormat::AQUA . $opponent[0] . ' ' . TextFormat::GREEN . round((int)$opponent[1] / 2, 1) . TextFormat::RED . CustomIcon::HEART);
            }
            $scoreboard->setLine($arena->getPlayers(), 8, CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . date('i:s', $time - $timePassed));
        } else {
            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $opponent = [['UNKNOWN', '0'], ['UNKNOWN', '0']];
                foreach ($arena->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent[$key++] = [$arena->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $health];
                    }
                    break;
                }
                $scoreboard->setLine($aliveTeam->getPlayers(), 6, TextFormat::AQUA . $opponent[0][0] . ' ' . TextFormat::GREEN . round((int)$opponent[0][1] / 2, 1) . TextFormat::RED . CustomIcon::HEART);
                $scoreboard->setLine($aliveTeam->getPlayers(), 5, TextFormat::AQUA . $opponent[1][0] . ' ' . TextFormat::GREEN . round((int)$opponent[1][1] / 2, 1) . TextFormat::RED . CustomIcon::HEART);
            }
            $scoreboard->setLine($arena->getPlayers(), 9, CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . date('i:s', $time - $timePassed));
        }
    }
}