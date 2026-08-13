<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\entities\PrimedTNT;
use skywars\SWArena;

class IgnitedTNT extends BaseDrop
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
        $motion = (new Random())->nextSignedFloat() * M_PI * 2;

        $position = $block->getPosition();
        $tnt = new PrimedTNT(Location::fromObject($position->add(0.5, 0, 0.5), $position->getWorld()));
        $tnt->setMotion(new Vector3(-sin($motion) * 0.02, 0.2, -cos($motion) * 0.02));
        $tnt->setOwningEntity($block);
        $tnt->spawnToAll();
    }
}