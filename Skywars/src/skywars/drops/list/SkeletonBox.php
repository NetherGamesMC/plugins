<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\entities\SWSkeleton;
use skywars\SWArena;

class SkeletonBox extends BaseDrop
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

        for ($x = -2; $x <= 2; $x++) {
            for ($z = -2; $z <= 2; $z++) {
                for ($y = -1; $y <= 3; $y++) {
                    if ($x > -2 && $x < 2 && $z > -2 && $z < 2 && $y > -1 && $y < 3) {
                        continue;
                    }
                    $selection->add($vector->x + $x, $vector->y + $y, $vector->z + $z, VanillaBlocks::GLASS());
                }
            }
        }

        for ($i = 0; $i < 4; $i++) {
            $pos = clone $location;

            $pos->x += mt_rand(-1, 1);
            $pos->z += mt_rand(-1, 1);

            $skeleton = new SWSkeleton($pos);

            $skeleton->setTargetEntity($player);
            $skeleton->entityBaseTick();

            $skeleton->spawnToAll();
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}