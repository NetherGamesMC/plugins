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

namespace libVanilla\entity\neutral;

use libVanilla\entity\hostile\Zombie;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ZombiePigman extends Zombie
{
    /** @var int */
    private int $angry = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(20);
        $this->setHealth(20);

        $this->setDamages([0, 5, 8, 12]);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ZOMBIE_PIGMAN;
    }

    public function getDefaultHeldItem(): Item
    {
        return VanillaItems::GOLDEN_SWORD();
    }

    public function getName(): string
    {
        return 'Zombie Pigman';
    }

    public function isInteresting(Entity $entity): bool
    {
        return $this->isAngry() && parent::isInteresting($entity);
    }

    public function isAngry(): bool
    {
        return $this->angry > 0;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human) {
            $this->setAngry();
        }
    }

    public function setAngry(?int $second = null): void
    {
        $this->angry = ($second ?? mt_rand(20, 40)) * 20;
    }

    public function onAttackTick(): void
    {
        parent::onAttackTick();
        $this->setSpeed(2.7);
    }

    public function resetAttack(): void
    {
        parent::resetAttack();
        $this->setSpeed(1.0);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setInt('Angry', $this->angry);

        return $nbt;
    }

    public function onUpdate(int $currentTick): bool
    {
        $parent = parent::onUpdate($currentTick);

        if ($parent && $this->angry > 0) {
            --$this->angry;
        }

        return $parent;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setAngry($nbt->getInt('Angry', 0));
    }

}