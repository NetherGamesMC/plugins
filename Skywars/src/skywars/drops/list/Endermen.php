<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use libVanilla\entity\neutral\Enderman;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class Endermen extends BaseDrop
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
        $selection = new Selection();

        $blocks = $block->getWorld()->getCollisionBlocks($block->getBoundingBox()->expandedCopy(2, 2, 2));

        foreach ($blocks as $b) {
            $selection->addBlock($b->getPosition()->asVector3(), VanillaBlocks::END_STONE());
        }

        for ($i = 0; $i < 3; $i++) {
            $pos = clone $location;

            $pos->x += mt_rand(-3, 3);
            $pos->z += mt_rand(-3, 3);

            $enderman = new Enderman($pos);
            $enderman->setCanSaveWithChunk(false);

            $enderman->setNameTag('Jeff');
            $enderman->setNameTagAlwaysVisible();
            $enderman->setTargetEntity($player);

            $enderman->spawnToAll();
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}