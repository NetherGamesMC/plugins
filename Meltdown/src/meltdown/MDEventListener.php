<?php

namespace meltdown;

use libminigames\MinigameListener;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use meltdown\arena\MDArena;

class MDEventListener extends MinigameListener implements Listener
{
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $arena = $this->getPlugin()->getArenaByWorld($event->getPlayer()->getWorld());

        if ($arena instanceof MDArena && $arena->isRunning()) {
            $arena->getListener()->onPlayerMove($event);
        }
    }
}