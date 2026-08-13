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

namespace mommasays\games;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerInteractEvent;

class GameClickFlower extends Game
{
    public function setupArena(): void
    {
        $poppy = VanillaBlocks::POPPY()->asItem();

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getInventory()->setItem(random_int(0, 8), $poppy);
        }
    }

    public function resetArena(): void
    {
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getInventory()->clearAll();
        }
    }

    public function getMessage(): string
    {
        return 'Click the flower';
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->equals(VanillaBlocks::POPPY()->asItem())) {
            if (!$this->isWinner($player->getName())) {
                $this->addWinner($player);
            }

            $player->getInventory()->clearAll();
        }
    }
}