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

namespace duels;

use duels\utils\Items;
use duels\utils\StatsData;
use libminigames\Team;
use libminigames\TeamArena;
use pocketmine\player\Player;
use function str_replace;

class DuelsTeam extends Team
{
    public function getPlayerName(Player $player, bool $nametag = false): string
    {
        if ($this->getArena()->isSoloGame()) {
            return $player->getDisplayName();
        }

        return parent::getPlayerName($player, $nametag);
    }

    /**
     * @return DuelsArena
     */
    public function getArena(): TeamArena
    {
        /** @var DuelsArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if ($this->getArena()->isWaiting()) {
            $inventory = $player->getInventory();
            if ($this->getArena()->isSoloGame()) {
                $inventory->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getTypeSelectionAnvil());
            } else {
                $inventory->setItem(Items::EXTRA_ITEM_1, Items::getTypeSelectionAnvil());
            }
        }
    }

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        parent::removePlayer($player, $teamChange);

        if ($this->getArena()->isRunning() && !$this->getArena()->isSpectator($player)) {
            $ess = $this->getArena()->getPlugin()->getEssentials();

            if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $this->getArena()->getPlugin()->getRandomKillMessage(1)), true);

                $this->getArena()->addKill($damager, $player);
            }

            $statsData = $this->getArena()->getStatsData();
            $statsData->addValue($player, StatsData::DEATHS);
            $statsData->addValue($player, StatsData::DUELS_DEATHS);

            if ($this->getSize() > 1) {
                foreach ($player->getDrops() as $item) {
                    $player->getWorld()->dropItem($player->getLocation(), $item);
                }
            }
        } elseif ($this->getArena()->isWaiting()) {
            $this->getArena()->removeTypeVote($player);
        }
    }
}