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
use bridges\BridgeTeam;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use function count;
use function in_array;

class ArenaTickTask extends Task
{
    /** @var BridgeArena */
    private BridgeArena $arena;

    public function __construct(BridgeArena $arena)
    {
        $this->arena = $arena;
    }

    public function onRun(): void
    {
        $arena = $this->arena;

        if (!$arena->isRunning()) {
            $this->getHandler()->cancel();
            return;
        }

        if ($arena->canScoreGoal()) {
            $world = $arena->getWorld();
            $goals = [];

            foreach ($arena->getTeams() as $team) {
                foreach ($world->getNearbyEntities($team->getPointAABB()) as $player) {
                    if ($player instanceof Player && !$player->isSpectator()) {
                        if (in_array($player, $team->getPlayers(), true)) {
                            $team->respawnPlayer($player);
                            $player->sendMessage(TextFormat::RED . "You can't score in your own goal!");
                        } elseif (in_array($player, ($otherTeam = $arena->getOtherTeam($team))->getPlayers(), true)) {
                            $goals[] = [$otherTeam, $player];
                            break;
                        }
                    }
                }
            }

            if (($goalCount = count($goals)) === 2) {
                $arena->phase = BridgeArena::PHASE_RESTART;
                $arena->time = 6;

                $arena->spawnCages();

                foreach ($arena->getTeams() as $team) {
                    $team->respawnAllPlayers();
                }

                $arena->broadcastTitle(TextFormat::BOLD . TextFormat::YELLOW . 'DRAW!');
            } elseif ($goalCount === 1) {
                /** @var Player $player */
                /** @var BridgeTeam $scoringTeam */
                [$scoringTeam, $player] = $goals[0];
                $scoringTeam->addGoal($player);
            } else {
                $pointY = $arena->getTeams()[0]->getPoint()->getY();
                foreach ($arena->getAlivePlayers() as $player) {
                    if (($pointY - 8) >= $player->getLocation()->getY()) {
                        $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_VOID, $player->getMaxHealth()));
                        continue;
                    }

                    $xpManager = $player->getXpManager();
                    if (($level = $xpManager->getXpLevel()) < 1) {
                        continue;
                    }

                    if (($progress = $xpManager->getXpProgress()) < 0.1) {
                        $xpManager->subtractXpLevels(1);
                        if ($level > 1) {
                            $xpManager->setXpProgress(1);
                        }
                    } else {
                        $xpManager->setXpProgress($progress - 0.1);
                    }
                }
            }
        }
    }
}