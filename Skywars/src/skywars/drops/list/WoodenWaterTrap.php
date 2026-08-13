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

class WoodenWaterTrap extends BaseDrop
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
        $location = $player->getLocation();

        $vector = $location->floor();
        $selection = new Selection();

        for ($x = -1; $x <= 1; $x++) {
            for ($z = -1; $z <= 1; $z++) {
                for ($y = -1; $y <= 3; $y++) {
                    if ($x > -1 && $x < 1 && $z > -1 && $z < 1 && $y > -1 && $y < 3) {
                        $selection->add($vector->x + $x, $vector->y + $y, $vector->z + $z, VanillaBlocks::WATER());
                    } else {
                        $selection->add($vector->x + $x, $vector->y + $y, $vector->z + $z, VanillaBlocks::OAK_PLANKS());
                    }
                }
            }
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}