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
use mommasays\MommaSays;
use mommasays\tasks\GameStandBlockTask;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;

class GameStandBlock extends Game
{
    public const TASK_DETERMINATION_DELAY = 10 * 20; // This is how long the task takes until evaluation results, this is being used for both Stand and NoStand Gamess

    use BlockPlaceTrait;

    public function getMessage(): string
    {
        return 'Stand on the block in your inventory';
    }

    public function setupArena(): void
    {
        $world = $this->getArena()->getWorld();

        $dyeColors = DyeColor::cases();
        $this->replaceMultiple($world, $dyeColors);
        $playerMappedMetas = [];

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $dyeColor = $dyeColors[array_rand($dyeColors)];
            $player->getInventory()->setItem(4, VanillaBlocks::STAINED_CLAY()->setColor($dyeColor)->asItem());
            $playerMappedMetas[$player->getName()] = $dyeColor;
        }

        MommaSays::getInstance()->getScheduler()->scheduleDelayedTask(new GameStandBlockTask($this->getArena(), $playerMappedMetas), self::TASK_DETERMINATION_DELAY);
    }

    public function resetArena(): void
    {
        $this->replaceSingle($this->getArena()->getWorld(), VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN));

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getInventory()->clearAll();
        }
    }
}