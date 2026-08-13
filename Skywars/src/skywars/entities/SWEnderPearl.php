<?php

declare(strict_types=1);

namespace skywars\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;

class SWEnderPearl extends EnderPearl
{
    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $nbt);
        $this->setCanSaveWithChunk(false);
    }

    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();

        if ($owner !== null) {
            $this->getWorld()->addParticle($this->getPosition(), new EndermanTeleportParticle());
            $this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());
            $owner->teleport($event->getRayTraceResult()->getHitVector());
            $this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());

            $owner->attack(new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_FALL, 1));
        }
    }
}