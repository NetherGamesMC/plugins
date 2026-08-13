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
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\object;

use libVanilla\session\WorldSessionManager;
use libVanilla\sound\TridentReturnSound;
use libVanilla\sound\TridentThunderSound;
use pocketmine\block\Block;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Trident as PMTrident;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class ThrownTrident extends PMTrident
{

    public const TAG_TRIDENT_OWNER = "Owner";
    private const MAX_LIFETIME = 1200;

    private ?string $ownerUuid = null;

    private bool $returning = false;

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->ownerUuid = $nbt->getTag(self::TAG_TRIDENT_OWNER) ? $nbt->getString(self::TAG_TRIDENT_OWNER) : null;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        if ($this->ownerUuid !== null) {
            $nbt->setString(self::TAG_TRIDENT_OWNER, $this->ownerUuid);
        }

        return $nbt;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);
        if ($this->ticksLived > self::MAX_LIFETIME) {
            $this->flagForDespawn();
            $hasUpdate = true;
        }

        return $hasUpdate;
    }

    public function hasMovementUpdate(): bool
    {
        return $this->returning || parent::hasMovementUpdate();
    }

    public function move(float $dx, float $dy, float $dz): void
    {
        if ($this->returning) {
            $owner = $this->getOwningEntity();
            if (!$owner instanceof Entity) {
                return;
            }

            $loyalty = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::LOYALTY);
            $speed = $this->item->getEnchantmentLevel($loyalty) / 20;
            $motion = $owner->getOffsetPosition($owner->location)->subtractVector($this->location)->multiply($speed);

            $this->location->pitch = 0;

            $xDist = $owner->location->x - $this->location->x;
            $zDist = $owner->location->z - $this->location->z;
            $this->location->yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
            if ($this->location->yaw < 0) {
                $this->location->yaw += 360.0;
            }

            Entity::move($motion->x, $motion->y, $motion->z);
            return;
        }

        parent::move($dx, $dy, $dz);
    }

    protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end): ?RayTraceResult
    {
        return $this->returning ? null : parent::calculateInterceptWithBlock($block, $start, $end); // it can pass through anything while returning
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        parent::onHitEntity($entityHit, $hitResult);

        $entityWorld = $entityHit->location->world;
        if (
            WorldSessionManager::getInstance()->get($entityWorld)->getCurrentWeather()->isThunderstorm() &&
            $this->item->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::CHANNELING)) &&
            !$entityHit->isUnderwater() &&
            $entityHit->location->getFloorY() > $entityWorld->getHighestBlockAt($entityHit->location->getFloorX(), $entityHit->location->getFloorZ())
        ) {
            if (LightningBolt::summon($entityHit->getLocation())) {
                $this->broadcastSound(new TridentThunderSound());
            }
        }
    }

    protected function onHit(ProjectileHitEvent $event): void
    {
        if ($this->item->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::LOYALTY))) {
            $this->returning = true;
            $this->networkPropertiesDirty = true;
            $this->broadcastSound(new TridentReturnSound());
        }
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        $properties->setGenericFlag(EntityMetadataFlags::SHOW_TRIDENT_ROPE, $this->returning);
        if (($owner = $this->getOwningEntityId()) !== null) {
            $properties->setLong(EntityMetadataProperties::OWNER_EID, $owner);
        }
    }

    public function setOwningEntity(?Entity $owner): void
    {
        if ($owner instanceof Player) {
            $this->ownerUuid = $owner->getUniqueId()->getBytes();
        }
        parent::setOwningEntity($owner);
    }

    public function getOwningEntity(): ?Entity
    {
        return $this->ownerUuid !== null ? $this->server->getPlayerByRawUUID($this->ownerUuid) : parent::getOwningEntity();
    }

    public function getOwningEntityId(): ?int
    {
        return $this->getOwningEntity()?->getId();
    }

    public function getResultDamage(): int
    {
        return (int)ceil($this->motion->normalize()->length() * $this->damage); // for some reason when fully charged, the resulting damage goes upto 2.0... not acceptable, too OP
    }
}