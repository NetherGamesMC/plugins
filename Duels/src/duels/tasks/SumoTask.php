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

namespace duels\tasks;

use duels\DuelsArena;
use duels\DuelsArenaListener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use function count;
use function str_replace;

class SumoTask extends Task
{
    /** @var DuelsArena */
    public DuelsArena $arena;

    public function __construct(DuelsArena $arena)
    {
        $this->arena = $arena;
    }

    public function onRun(): void
    {
        $arena = $this->arena;

        if (!$arena->isRunning()) {
            $this->getHandler()?->cancel();
            return;
        }

        $spawn = $arena->getWorld()->getSafeSpawn();
        foreach ($arena->getAlivePlayers() as $player) {
            $location = $player->getLocation();
            if ($location->distance($spawn) > 15 || $location->getY() < $spawn->getY()) {
                $team = $arena->getTeam($player);
                $plugin = $arena->getPlugin();

                if (($damager = $plugin->getEssentials()->getCombatLogger()->getLatestHit($player)) !== null) {
                    if ($arena->isSoloGame()) {
                        $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $plugin->getRandomKillMessage(EntityDamageEvent::CAUSE_VOID, true)), true);
                    } elseif (($damagerTeam = $arena->getTeamNull($damager)) !== null) {
                        $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $damagerTeam->getPlayerName($damager)], $plugin->getRandomKillMessage(EntityDamageEvent::CAUSE_VOID, true)), true);
                    }

                    $arena->addKill($damager, $player);
                } elseif ($arena->isSoloGame()) {
                    $arena->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $plugin->getRandomKillMessage(EntityDamageEvent::CAUSE_VOID)), true);
                } else {
                    $arena->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $plugin->getRandomKillMessage(EntityDamageEvent::CAUSE_VOID)), true);
                }

                /** @var DuelsArenaListener $listener */
                $listener = $arena->getListener();
                $listener->onPlayerDeath($player, $team, false);

                if (count($arena->getAliveTeams()) <= 1) {
                    $this->getHandler()?->cancel();
                    return;
                }
            }
        }
    }
}
