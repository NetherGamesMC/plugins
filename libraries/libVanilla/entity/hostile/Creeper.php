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

namespace libVanilla\entity\hostile;

use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Monster;
use libVanilla\entity\utils\EntitySizeUtils;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Explosive;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\Explosion;
use pocketmine\world\sound\IgniteSound;
use function mt_rand;

class Creeper extends Monster implements Explosive
{
    use WalkEntityTrait {
        WalkEntityTrait::doMovement as baseDoMovement;
    }

    /** @var float */
    private float $force = 3.0;
    private int $fuse = -1;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(20);
        $this->setHealth(20);

        $this->setSpeed(0.95);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CREEPER;
    }

    public function getName(): string
    {
        return 'Creeper';
    }

    public function getInteractDistance(): float
    {
        return 3;
    }

    public function resetAttack(): void
    {
        parent::resetAttack();
        $this->setSpeed(0.95);
        if ($this->isIgnited()) {
            $this->resetFuse();
        }
    }

    public function ignite(int $fuseLength): void
    {
        $this->fuse = $fuseLength;
        $this->networkPropertiesDirty = true;
        $this->getWorld()->addSound($this->getPosition(), new IgniteSound());
    }

    public function resetFuse(): void
    {
        $this->fuse = -1;
        $this->networkPropertiesDirty = true;
    }

    public function isIgnited(): bool
    {
        return $this->fuse >= 0;
    }

    public function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::IGNITED, $this->isIgnited());
        if ($this->isIgnited()) {
            $properties->setInt(EntityMetadataProperties::FUSE_LENGTH, $this->fuse);
        }
    }

    public function onAttackTick(): void
    {
        if ($this->fuse > 0) {
            $this->fuse--;
        } elseif ($this->fuse === 0) {
            $this->explode();
        }
        parent::onAttackTick();
        $this->setSpeed(0.4);
    }

    public function doMovement(Vector3 $location): void
    {
        if ($this->isIgnited()) {
            return; // do not move when ignited
        }
        $this->baseDoMovement($location);
    }

    public function interactTarget(): void
    {
        if (!$this->isIgnited()) {
            $this->ignite(30);
        }
    }

    public function explode(): void
    {
        $ev = new EntityPreExplodeEvent($this, $this->getForce());
        $ev->call();

        $this->flagForDespawn();
        if (!$ev->isCancelled()) {
            $explosion = new Explosion($this->getPosition(), $ev->getRadius(), $this);
            if ($ev->isBlockBreaking()) {
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

    public function getForce(): float
    {
        return $this->force;
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2))
        ];
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return 5;
        }

        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return EntitySizeUtils::upright(1.8, 0.6);
    }
}
