<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class WebTrap extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 50;
    }

    public function getPriority(): int
    {
        return self::PRIORITY_MEDIUM;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $location = $block->getLocation();

        $vector = $location->floor();
        $selection = new Selection();

        for ($x = -2; $x <= 2; $x++) {
            for ($z = -2; $z <= 2; $z++) {
                $selection->add($vector->x + $x, $vector->y - 1, $vector->z + $z, VanillaBlocks::COBWEB());
            }
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}