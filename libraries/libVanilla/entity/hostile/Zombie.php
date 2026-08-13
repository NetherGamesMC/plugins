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

use libVanilla\entity\AgeableMonster;
use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\traits\ItemInventoryTrait;
use libVanilla\entity\utils\EntitySizeUtils;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Zombie extends AgeableMonster
{
    use WalkEntityTrait, ItemInventoryTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setSpeed(0.9);

        $this->setMaxHealth(20);
        $this->setHealth(20);

        $this->setDamages([0, 2.5, 3, 4.5]);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ZOMBIE;
    }

    public function getName(): string
    {
        return 'Zombie';
    }

    public function interactTarget(): void
    {
        /** @var Entity $target */
        $target = $this->getTargetEntity();

        $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
        $target->attack($ev);

        $this->broadcastAnimation(new ArmSwingAnimation($this));
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        $drops = [
            VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
        ];

        if (mt_rand(0, 199) < 5) {
            $drops[] = match (mt_rand(0, 2)) {
                0 => VanillaItems::IRON_INGOT(),
                1 => VanillaItems::CARROT(),
                default => VanillaItems::POTATO(),
            };
        }

        return $drops;
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return $this->isBaby() ? 12 : 5;
        }

        return 0;
    }

    public function setBaby(bool $baby): void
    {
        $this->baby = $baby;
        $this->setScale($baby ? 0.75 : 1.0);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return EntitySizeUtils::upright(1.9, 0.6);
    }
}
