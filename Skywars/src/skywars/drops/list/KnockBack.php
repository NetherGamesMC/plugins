<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;
use skywars\utils\SoundNames;

class KnockBack extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 50;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $direction = $player->getDirectionVector()->multiply(-2);
        $player->knockBack($direction->x, $direction->z, 1);

        $this->playSound($player->getLocation(), SoundNames::SOUND_MOB_WITHER_SHOOT);
    }
}