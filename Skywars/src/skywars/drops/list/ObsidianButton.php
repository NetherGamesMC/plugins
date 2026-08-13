<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class ObsidianButton extends BaseDrop
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
        $block->getWorld()->setBlock($block->getLocation()->asVector3()->floor(), VanillaBlocks::OBSIDIAN());
        $block->getWorld()->setBlock($block->getLocation()->asVector3()->add(0, 1, 0)->floor(), VanillaBlocks::OAK_BUTTON()->setFacing(Facing::UP));
    }
}