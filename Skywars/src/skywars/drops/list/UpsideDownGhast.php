<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libVanilla\entity\hostile\Ghast;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class UpsideDownGhast extends BaseDrop
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
        $ghast = new Ghast($block->getLocation());
        $ghast->setCanSaveWithChunk(false);

        $ghast->setNameTag("Dinnerbone");
        $ghast->setTargetEntity($player);

        $ghast->spawnToAll();
    }
}