<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class FoodRain extends BaseDrop
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

        for ($i = 0; $i < 20; $i++) {
            $pos = clone $location;

            $pos->x += mt_rand(-3, 3);
            $pos->y += 10;
            $pos->z += mt_rand(-3, 3);

            $player->getWorld()->dropItem($pos->asVector3(), VanillaItems::COOKED_RABBIT()->setCount(1));
        }
    }
}