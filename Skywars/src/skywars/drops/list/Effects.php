<?php

declare(strict_types=1);

namespace skywars\drops\list;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class Effects extends BaseDrop
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
        $effects = [
            new EffectInstance(VanillaEffects::LEVITATION(), mt_rand(20, 60), 3),
            new EffectInstance(VanillaEffects::WITHER(), mt_rand(20, 100), 3),
            new EffectInstance(VanillaEffects::REGENERATION(), mt_rand(100, 200)),
            new EffectInstance(VanillaEffects::BLINDNESS(), 40)
        ];
        $player->getEffects()->add($effects[array_rand($effects)]);
    }
}