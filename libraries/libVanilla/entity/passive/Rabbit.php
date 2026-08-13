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

namespace libVanilla\entity\passive;

use libVanilla\entity\ai\JumpingEntityTrait;
use libVanilla\entity\Animal;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use function mt_rand;

class Rabbit extends Animal
{
    use JumpingEntityTrait;

    protected int $jumpDuration = 0;

    private const DATA_JUMP_DURATION = 10; // byte (found with BDS + gt, name was sourced from nukkit)

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(3);
        $this->setHealth(3);

        $this->setSpeed(2);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::RABBIT;
    }

    public function getName(): string
    {
        return 'Rabbit';
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        $drops = [];

        if (!$this->isBaby()) {
            $drops[] = VanillaItems::RABBIT_HIDE()->setCount(mt_rand(0, 1));
            $drops[] = ($this->isOnFire() ? VanillaItems::COOKED_RABBIT() : VanillaItems::RAW_RABBIT())->setCount(mt_rand(0, 1));

            if (mt_rand(0, 9) === 0) { // at 10 percent chance, rabbits drop rabbit's foot
                $drops[] = VanillaItems::RABBIT_FOOT();
            }
        }

        return $drops;
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return $this->isBaby() ? 0 : mt_rand(1, 3);
        }

        return 0;
    }

    /**
     * @return int[]
     */
    public function getBreedingItems(): array
    {
        return [
            ItemTypeIds::CARROT,
            ItemTypeIds::GOLDEN_CARROT
        ];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.402, 0.402);
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        $properties->setByte(self::DATA_JUMP_DURATION, $this->jumpDuration);
    }

    public function setJumpDuration(int $jumpDuration): void
    {
        $this->jumpDuration = $jumpDuration;
        $this->networkPropertiesDirty = true;
    }

    public function jump(): void
    {
        // from BDS this is 3 when jumping then 0 upon landing
        // but the rabbits in vanilla jump smaller distances...
        // linearly estimate it based on motion length perhaps?
        $this->setJumpDuration(20);
        parent::jump();
    }

    protected function onHitGround(): ?float
    {
        $this->setJumpDuration(0);
        return parent::onHitGround();
    }
}
