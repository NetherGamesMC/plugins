<?php

declare(strict_types=1);

namespace libVanilla\listener;

use libVanilla\entity\passive\Chicken;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Egg;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;

final class EntityListener implements Listener
{
    /**
     * @param ProjectileHitBlockEvent $event
     *
     * @priority MONITOR
     */
    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        if ($event->getEntity() instanceof Egg && mt_rand(0, 31) === 0) {
            $vector = $event->getRayTraceResult()->getHitVector();
            $world = $event->getBlockHit()->getPosition()->getWorld();

            $chicken = new Chicken(Location::fromObject($vector, $world));
            $chicken->setAge(-20 * 60 * 20);
            $chicken->spawnToAll();
        }
    }
}