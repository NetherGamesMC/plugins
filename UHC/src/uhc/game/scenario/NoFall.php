<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\event\entity\EntityDamageEvent;
use uhc\game\scenario\base\Scenario;

class NoFall extends Scenario
{

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $event->cancel();
        }
    }
}