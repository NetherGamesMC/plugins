<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\event\entity\EntityDamageEvent;
use uhc\game\scenario\base\Scenario;

class Fireless extends Scenario
{

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if (
            $event->getCause() === EntityDamageEvent::CAUSE_FIRE ||
            $event->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK ||
            $event->getCause() === EntityDamageEvent::CAUSE_LAVA
        ) {
            $event->getEntity()->extinguish();
            $event->cancel();
        }
    }
}