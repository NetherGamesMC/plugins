<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\RayTraceResult;
use function sqrt;

class Arrow extends \pocketmine\entity\projectile\Arrow
{
    use TrailCosmeticTrait;

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void //THIS FUNCTION CAN BE REMOVED WHEN ProjectileHitEntityEvent is cancellable
    {
        $damage = $this->getResultDamage();

        if ($damage >= 0) {
            if ($this->getOwningEntity() === null) {
                $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            } else {
                $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }

            $entityHit->attack($ev);

            if ($this->punchKnockback > 0 && !$ev->isCancelled()) {
                $horizontalSpeed = sqrt($this->motion->x ** 2 + $this->motion->z ** 2);
                if ($horizontalSpeed > 0) {
                    $multiplier = $this->punchKnockback * 0.6 / $horizontalSpeed;
                    $entityHit->setMotion($entityHit->getMotion()->add($this->motion->x * $multiplier, 0.1, $this->motion->z * $multiplier));
                }
            }

            if ($this->isOnFire()) {
                $ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
                $ev->call();
                if (!$ev->isCancelled()) {
                    $entityHit->setOnFire($ev->getDuration());
                }
            }
        }

        $this->flagForDespawn();
    }
}