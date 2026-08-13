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

use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Monster;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class Spider extends Monster
{
    use WalkEntityTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(16);
        $this->setHealth(16);

        $this->setDamages([0, 2, 3, 4]);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SPIDER;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setCanClimbWalls(true);
    }

    public function getName(): string
    {
        return 'Spider';
    }

    public function interactTarget(): void
    {
        /** @var Entity $target */
        $target = $this->getTargetEntity();

        $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
        $target->attack($ev);
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        $drops = [
            VanillaItems::STRING()->setCount(mt_rand(0, 2))
        ];

        if ($this->lastDamageCause instanceof EntityDamageByEntityEvent && $this->lastDamageCause->getDamager() instanceof Player && mt_rand(1, 3) === 1) {
            $drops[] = VanillaItems::SPIDER_EYE();
        }

        return $drops;
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
        return new EntitySizeInfo(0.9, 1.4);
    }
}
