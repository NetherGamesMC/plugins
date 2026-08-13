<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Drew, Driesboy
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\object;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\Explosion;
use pocketmine\world\particle\CriticalParticle;

class Fireball extends Throwable
{
    /** @var int */
    private int $age = 0;

    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $nbt);

        $this->setHasGravity(false);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::FIREBALL;
    }

    public function onHit(ProjectileHitEvent $event): void
    {
        $this->getWorld()->addParticle($this->location->add($this->size->getWidth() / 2 + mt_rand(-100, 100) / 500, $this->size->getHeight() / 2 + mt_rand(-100, 100) / 500, $this->size->getWidth() / 2 + mt_rand(-100, 100) / 500), new CriticalParticle());

        $ev = new EntityPreExplodeEvent($this, 4);
        $ev->call();

        if (!$ev->isCancelled()) {
            $explosion = new Explosion($this->location, $ev->getRadius(), $this);

            if ($ev->isBlockBreaking()) {
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->isCollided || $this->age > 300) {
            $this->flagForDespawn();
        }
        $this->age += $tickDiff;

        return $hasUpdate;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if ($this->age < 20 && $source->getEntity() === $this->getOwningEntity()) {
            $source->cancel();
        }

        if ($source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Player && !$source->isCancelled()) {
            $this->setMotion($this->getMotion()->multiply(-1));
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.31, 0.31);
    }
}
