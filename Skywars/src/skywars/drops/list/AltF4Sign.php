<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\block\utils\SignText;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class AltF4Sign extends BaseDrop
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
        $sign = VanillaBlocks::OAK_SIGN();
        $rotation = ((int)floor((($player->getLocation()->yaw + 180) * 16 / 360) + 0.5)) & 0xf;
        $sign->setRotation($rotation);
        $sign->setText(new SignText(['', 'Alt f4 for win', '', '']));
        $block->getWorld()->setBlock($block->getLocation()->asVector3()->floor(), $sign);
    }
}