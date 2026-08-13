<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\entity\projectile\Arrow;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class ArrowRain extends BaseDrop
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

        for ($x = -3; $x <= 3; $x++) {
            for ($z = -3; $z <= 3; $z++) {
                $pos = clone $location;

                $pos->x += $x;
                $pos->y += 3;
                $pos->z += $z;

                $arrow = new Arrow($pos, $block, false);
                $arrow->spawnToAll();
            }
        }
    }
}