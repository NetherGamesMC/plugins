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
 * @author Drew, Driesboy, sylvrs, CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\object;

use libVanilla\item\FishingRod;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class FishingHook extends Projectile
{
    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null, private bool $combatHook = false)
    {
        parent::__construct($location, $shootingEntity, $nbt);

        $this->setCanSaveWithChunk(false);
    }

    public function setCombatHook(bool $combatHook): void
    {
        $this->combatHook = $combatHook;
    }

    public function isCombatHook(): bool
    {
        return $this->combatHook;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::FISHING_HOOK;
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
        $ev->call();

        $damage = $this->getResultDamage();

        if ($this->getOwningEntity() === null) {
            $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        } else {
            $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        }

        $entityHit->attack($ev);
        if ($ev->isCancelled()) {
            return;
        }

        $this->setTargetEntity($entityHit);

        if (!$this->combatHook) {
            NetworkBroadcastUtils::broadcastPackets($entityHit->getViewers(), [
                SetActorLinkPacket::create(new EntityLink($entityHit->getId(), $this->getId(), EntityLink::TYPE_RIDER, true, true, 0.0))
            ]);
            $this->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, $entityHit->getSize()->getHeight(), 0));
        }

        $owner = $this->getOwningEntity();
        if ($entityHit instanceof Living && $owner !== null) {
            $diff = $entityHit->getPosition()->subtractVector($owner->getPosition());
            $entityHit->knockBack($diff->x, $diff->z, 0.2);
            if ($this->combatHook) {
                $this->flagForDespawn();
            }
        }
    }

    public function getResultDamage(): int
    {
        return 0;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $targetEntity = $this->getTargetEntity();
        $owner = $this->getOwningEntity();
        if (
            !$owner instanceof Player ||
            !$owner->getInventory()->getItemInHand() instanceof FishingRod ||
            !$owner->isAlive() ||
            !$owner->isConnected() ||
            !$owner->isValid() ||
            $owner->getWorld() !== $this->getWorld()
        ) {
            $this->flagForDespawn();
            return $hasUpdate;
        }

        if ($targetEntity instanceof Entity && !$targetEntity->isClosed() && $targetEntity->isAlive() && $targetEntity->isValid() && $targetEntity->getWorld() === $this->location->getWorld()) {
            $this->location = $targetEntity->getLocation();
            $this->location->y += $targetEntity->getSize()->getHeight();
        }

        if ($this->isUnderwater()) {
            $this->motion->y += 0.25;
            $this->motion->y /= 2;
            $this->motion->x /= 2;
            $this->motion->z /= 2;
            $this->gravityEnabled = false;
        } else {
            $this->gravityEnabled = true;
        }
        return $hasUpdate;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.15, 0.15);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.01;
    }

    protected function getInitialGravity(): float
    {
        return 0.05;
    }
}
