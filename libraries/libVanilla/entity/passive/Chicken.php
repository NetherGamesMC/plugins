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

use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Animal;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\sound\PopSound;
use function mt_rand;

class Chicken extends Animal
{
    use WalkEntityTrait;

    public const DROP_EGG_DELAY_MIN = 6000;
    public const DROP_EGG_DELAY_MAX = 12000;

    /** @var int */
    private int $eggLayTime = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(4);
        $this->setHealth(4);

        $this->setSpeed(1.2);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    public function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->eggLayTime = $nbt->getInt('EggLayTime', mt_rand(self::DROP_EGG_DELAY_MIN, self::DROP_EGG_DELAY_MAX));
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setInt('EggLayTime', $this->eggLayTime);

        return $nbt;
    }

    public function getName(): string
    {
        return 'Chicken';
    }

    public function onUpdate(int $currentTick): bool
    {
        $parent = parent::onUpdate($currentTick);

        if (!$this->isOnGround() && $this->motion->y < 0) {
            $this->motion->y *= 0.6;
        }

        if ($parent) {
            if ($this->eggLayTime === 0) {
                $this->layEgg();

                $this->eggLayTime = mt_rand(self::DROP_EGG_DELAY_MIN, self::DROP_EGG_DELAY_MAX);
            }
            $this->eggLayTime--;
        }

        return $parent;
    }

    private function layEgg(): void
    {
        $world = $this->getWorld();

        $world->dropItem($this->getPosition(), VanillaItems::EGG());
        $world->addSound($this->getPosition(), new PopSound(), $this->getViewers());
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return $this->isBaby() ? [] : [
            ($this->isOnFire() ? VanillaItems::COOKED_CHICKEN() : VanillaItems::RAW_CHICKEN())->setCount(mt_rand(1, 3)),
            VanillaItems::FEATHER()->setCount(mt_rand(0, 2))
        ];
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
            ItemTypeIds::WHEAT_SEEDS,
            ItemTypeIds::BEETROOT_SEEDS,
            ItemTypeIds::MELON_SEEDS,
            ItemTypeIds::PUMPKIN_SEEDS,
        ];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.8, 0.6);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $source->cancel();
        }
        parent::attack($source);
    }
}
