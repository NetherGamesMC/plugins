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

use mommasays\games\traits\BlockPlaceTrait;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;

class GameMineCobble extends Game
{
    use BlockPlaceTrait;

    public function getMessage(): string
    {
        return 'Mine cobblestone';
    }

    public function setupArena(): void
    {
        $this->putBigBlockStack($this->getArena()->getWorld(), VanillaBlocks::COBBLESTONE());

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->teleport(new Vector3(Game::ARENA_SPAWN_POINT[0], Game::ARENA_SPAWN_POINT[1], Game::ARENA_SPAWN_POINT[2]));
            $player->getInventory()->setItem(0, VanillaItems::IRON_PICKAXE());
            $player->setGamemode(GameMode::SURVIVAL);
        }
    }

    public function resetArena(): void
    {
        $this->putBigBlockStack($this->getArena()->getWorld(), VanillaBlocks::AIR());

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->setGamemode(GameMode::ADVENTURE);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();

        if ($block->getTypeId() === BlockTypeIds::COBBLESTONE && !$this->isWinner($event->getPlayer()->getName())) {
            $this->addWinner($event->getPlayer());
        }

        $event->cancel();
    }

}