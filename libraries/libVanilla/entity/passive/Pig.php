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
use function mt_rand;

class Pig extends Animal
{
    use WalkEntityTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(10);
        $this->setHealth(10);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::PIG;
    }

    public function getName(): string
    {
        return 'Pig';
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return $this->isBaby() ? [] : [
            ($this->isOnFire() ? VanillaItems::COOKED_PORKCHOP() : VanillaItems::RAW_PORKCHOP())->setCount(mt_rand(1, 3))
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
            ItemTypeIds::CARROT,
            ItemTypeIds::POTATO,
            ItemTypeIds::BEETROOT
        ];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.9, 0.9);
    }
}
