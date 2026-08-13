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

namespace mommasays\tasks;

use mommasays\games\Game;
use mommasays\MSArena;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\StainedHardenedClay;
use pocketmine\block\utils\DyeColor;
use pocketmine\scheduler\Task;

class GameStandBlockTask extends Task
{
    /** @var MSArena */
    public MSArena $arena;
    /** @var DyeColor[] */
    public array $expectedBlocks = [];

    public function __construct(MSArena $arena, array $expectedBlocks)
    {
        $this->arena = $arena;
        $this->expectedBlocks = $expectedBlocks;
    }

    public function onRun(): void
    {
        foreach ($this->arena->getAlivePlayers() as $player) {
            /** @var Game $currentGame */
            $currentGame = $this->arena->getCurrentGame();
            /** @var StainedHardenedClay $blockUnder */
            $blockUnder = $player->getWorld()->getBlock($player->getLocation()->floor()->subtract(0, 1, 0));

            if (isset($this->expectedBlocks[$player->getName()]) && $blockUnder->getTypeId() === BlockTypeIds::STAINED_CLAY && $blockUnder->getColor() === $this->expectedBlocks[$player->getName()]) {
                $currentGame->addWinner($player);
            }
        }
    }
}