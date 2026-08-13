<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\DinnerboneSheep;
use skywars\entities\JebSheep;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class Sheep extends BaseDrop
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
        if (mt_rand(0, 1) === 0) {
            /** @var JebSheep|null $lastSheep */
            $lastSheep = null;

            for ($i = 0; $i < 6; $i++) {
                $jeb = new JebSheep($block->getLocation());
                $jeb->setNameTag('jeb_');

                $jeb->spawnToAll();

                $lastSheep?->ride($jeb);

                $lastSheep = $jeb;
            }
        } else {
            $dinnerbone = new DinnerboneSheep($block->getLocation());
            $dinnerbone->setNameTag('Dinnerbone');

            $dinnerbone->spawnToAll();
        }
    }
}