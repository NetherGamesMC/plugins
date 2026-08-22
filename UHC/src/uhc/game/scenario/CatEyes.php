<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use libminigames\events\arena\ArenaStartEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use uhc\game\scenario\base\Scenario;

class CatEyes extends Scenario
{

    public function onArenaStart(ArenaStartEvent $event): void
    {
        foreach ($event->getArena()->getAlivePlayers() as $player) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 2147483647, 1, false));
        }
    }
}