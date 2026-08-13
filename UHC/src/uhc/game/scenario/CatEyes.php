<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use libminigames\events\MinigameStartEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use uhc\game\scenario\base\Scenario;

class CatEyes extends Scenario
{

    public function onMinigameStart(MinigameStartEvent $event): void
    {
        $event->getPlayer()->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 2147483647, 1, false));
    }
}