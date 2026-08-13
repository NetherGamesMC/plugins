<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libVanilla\entity\passive\Chicken;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class ChickenRain extends BaseDrop
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

        for ($i = 0; $i < 10; $i++) {
            $pos = clone $location;

            $pos->x += mt_rand(-3, 3);
            $pos->y += 10;
            $pos->z += mt_rand(-3, 3);

            $chicken = new Chicken($pos);
            $chicken->setCanSaveWithChunk(false);

            $chicken->setNameTag('Duck');
            $chicken->setNameTagVisible();

            $chicken->spawnToAll();
        }
    }
}