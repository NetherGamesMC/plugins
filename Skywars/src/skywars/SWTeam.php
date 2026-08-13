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

namespace skywars;

use libminigames\Team;
use libminigames\TeamArena;
use libminigames\utils\StatsData as StatsDataAlias;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use skywars\utils\Items;
use skywars\utils\StatsData;
use function count;
use function str_replace;

class SWTeam extends Team
{
    public function getPlayerName(Player $player, bool $nametag = false): string
    {
        if ($this->getArena()->isSoloGame()) {
            return $player->getDisplayName();
        }

        return parent::getPlayerName($player, $nametag);
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

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        parent::removePlayer($player, $teamChange);

        $arena = $this->getArena();
        if ($arena->isRunning() && !$arena->isSpectator($player)) {
            $ess = $arena->getPlugin()->getEssentials();

            $combatLogger = $ess->getCombatLogger();
            $log = $combatLogger->getLog($player);
            foreach ($log->getAssists() as $assistName) {
                $arena->addAssist($assistName);
            }

            if (($damager = $combatLogger->getLatestHit($player)) !== null && $arena->isInArena($damager)) {
                $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $arena->getPlugin()->getRandomKillMessage(1)), true);

                $arena->addKill($damager, $player);
            }

            $statsData = $arena->getStatsData();
            $statsData->addValue($player, StatsDataAlias::DEATHS);
            if ($arena->isDuelsGame()) {
                $statsData->addValue($player, StatsData::DUELS_DEATHS);
            } else {
                $statsData->addValue($player, StatsData::SW_DEATHS);
                $statsData->addValue($player, StatsData::SW_MODE_DEATHS);
                $statsData->addValue($player, StatsData::SW_MODE_TYPE_DEATHS);
            }

            foreach ($player->getDrops() as $item) {
                if (!$item->equals(Items::getKitSelector())) {
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }

            if (!$arena->isDuelsGame()) {
                $arena->getScoreboard()->setLine($arena->getPlayers(), 8, CustomIcon::PLAYERS_TINY . TextFormat::GREEN . count($arena->getAlivePlayers()));
            }
        } elseif ($arena->isWaiting()) {
            $arena->removeTypeVote($player);
        }
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        $inventory = $player->getInventory();
        if ($this->getArena()->isSoloGame()) {
            if ($this->getArena()->isWaiting() && !$this->getArena()->isPrivateGame() && $player->hasPermission(Permissions::RANK_VOTER)) {
                $inventory->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getTypeSelectionAnvil());
            }
            $inventory->setItem(Items::EXTRA_ITEM_1, Items::getKitSelector());
        } else {
            if ($this->getArena()->isWaiting() && !$this->getArena()->isPrivateGame() && $player->hasPermission(Permissions::RANK_VOTER)) {
                $inventory->setItem(Items::EXTRA_ITEM_1, Items::getTypeSelectionAnvil());
            }
            $inventory->setItem(Items::EXTRA_ITEM_2, Items::getKitSelector());
        }
    }
}