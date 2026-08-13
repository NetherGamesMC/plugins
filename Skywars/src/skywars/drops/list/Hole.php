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

class Hole extends BaseDrop
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
                for ($y = mt_rand(-6, -5); $y <= 0; $y++) {
                    $selection->add($vector->x + $x, $vector->y + $y, $vector->z + $z, VanillaBlocks::AIR());
                }
            }
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}