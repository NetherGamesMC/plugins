<?php

declare(strict_types=1);


namespace libVanilla\entity\traits;


use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\world\sound\Sound;

trait ProjectileFireTrait
{
    abstract public function createProjectile(Location $location): ?Entity;

    public function interactTarget(): void
    {
        $location = $this->getLocation();

        $entity = $this->createProjectile(Location::fromObject(
            $this->getEyePos(),
            $this->getWorld(),
            $location->yaw,
            $location->pitch
        ));

        if ($entity === null) {
            return;
        }

        $entity->setMotion($entity->getDirectionVector());

        if ($entity instanceof Projectile) {
            $ev = new ProjectileLaunchEvent($entity);
            $ev->call();
            if ($ev->isCancelled()) {
                return;
            }

            $ev->getEntity()->spawnToAll();
        } else {
            $entity->spawnToAll();
        }
        $location->getWorld()->addSound($location, $this->getLaunchSound());

        parent::interactTarget();
    }

    abstract public function getLaunchSound(): Sound;
}