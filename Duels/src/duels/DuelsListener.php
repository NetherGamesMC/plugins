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

use libminigames\Minigame;
use libminigames\MinigameListener;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\entity\EntityExplodeEvent;

class DuelsListener extends MinigameListener
{
    /**
     * @param BlockBurnEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockBurn(BlockBurnEvent $event): void
    {
        if ($this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld()) !== null) {
            $event->cancel();
        }
    }

    /**
     * @return Duels
     */
    public function getPlugin(): Minigame
    {
        /** @var Duels $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    /**
     * @param EntityExplodeEvent $event
     *
     * @priority NORMAL
     */
    public function onExplode(EntityExplodeEvent $event): void
    {
        if ($this->getPlugin()->getArenaByWorld($event->getPosition()->getWorld()) !== null) {
            $event->setBlockList([]);
        }
    }
}