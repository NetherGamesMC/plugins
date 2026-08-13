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

use mommasays\MSArena;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\item\Item;
use pocketmine\player\GameMode;
use function array_fill;

class GamePlace extends Game
{
    /** @var Item[] */
    public array $items = [];

    public function __construct(MSArena $arena, bool $teleport)
    {
        parent::__construct($arena, $teleport);

        /** @var Block[] */
        $blocks = [
            VanillaBlocks::OAK_WOOD(),
            VanillaBlocks::GLASS(),
            VanillaBlocks::COBBLESTONE(),
            VanillaBlocks::STAINED_GLASS(),
            VanillaBlocks::BRICKS(),
            VanillaBlocks::EMERALD(),
            VanillaBlocks::REDSTONE(),
            VanillaBlocks::DIAMOND(),
            VanillaBlocks::EMERALD(),
        ];

        foreach ($blocks as $block) {
            $this->items[] = $block->asItem();
        }
    }

    public function getMessage(): string
    {
        return 'Place a block';
    }

    public function setupArena(): void
    {
        $item = $this->items[array_rand($this->items)];

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getInventory()->setContents(array_fill(0, 9, $item));
            $player->setGamemode(GameMode::SURVIVAL);
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$this->isWinner($player->getName())) {
            $this->addWinner($player);
        }

        $event->cancel();
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $event->cancel();
    }

    public function resetArena(): void
    {
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getInventory()->clearAll();
            $player->setGamemode(GameMode::ADVENTURE);
        }
    }
}