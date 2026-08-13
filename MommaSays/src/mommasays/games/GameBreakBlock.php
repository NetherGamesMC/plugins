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

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use mommasays\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;

class GameBreakBlock extends Game
{
    /** @var Block */
    private Block $block;

    public function getMessage(): string
    {
        return 'Break a block of ' . TextFormat::clean($this->getBlock()->getName());
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block->getStateId() === $this->block->getStateId() && !$this->isWinner($player->getName())) {
            $this->addWinner($player);
        }

        $event->cancel();
    }

    public function setupArena(): void
    {
        $this->block = Utils::getRandomBlock();

        $selection = new Selection();
        $selection->add(5, 50, 5, $this->block);
        $selection->add(-3, 50, 4, $this->block);
        $selection->add(-3, 50, -4, $this->block);
        $selection->add(5, 50, -4, $this->block);

        AsyncBlockManager::executeSet($selection, $this->getArena()->getWorld(), function (): void {
            foreach ($this->getArena()->getAlivePlayers() as $player) {
                $player->setGamemode(GameMode::SURVIVAL);
                $player->getInventory()->addItem(VanillaItems::STONE_PICKAXE());
            }
        });
    }

    public function resetArena(): void
    {
        $air = VanillaBlocks::AIR();

        $selection = new Selection();
        $selection->add(5, 50, 5, $air);
        $selection->add(-3, 50, 4, $air);
        $selection->add(-3, 50, -4, $air);
        $selection->add(5, 50, -4, $air);

        AsyncBlockManager::executeSet($selection, $this->getArena()->getWorld());

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->setGamemode(GameMode::ADVENTURE);
            $player->getInventory()->clearAll();
        }
    }
}