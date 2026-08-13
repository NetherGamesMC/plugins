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

use libVanilla\entity\ai\FlyEntityTrait;
use libVanilla\entity\ai\navigator\EntityNavigator;
use libVanilla\entity\Monster;
use libVanilla\entity\object\Fireball;
use libVanilla\entity\traits\ProjectileFireTrait;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\sound\GhastShootSound;
use pocketmine\world\sound\GhastSound;
use pocketmine\world\sound\Sound;
use function mt_rand;

class Ghast extends Monster
{
    use FlyEntityTrait, ProjectileFireTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(10);
        $this->setHealth(10);

        $this->setHasGravity(false);
        $this->setSpeed(0.9);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::GHAST;
    }

    public function getName(): string
    {
        return 'Ghast';
    }

    public function getFlyHeight(): int
    {
        return 10;
    }

    public function getInteractDistance(): float
    {
        return 20;
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaItems::GHAST_TEAR()->setCount(mt_rand(0, 1)),
            VanillaItems::GUNPOWDER()->setCount(mt_rand(0, 2)),
        ];
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return 5;
        }

        return 0;
    }

    public function getDefaultNavigator(): EntityNavigator
    {
        // todo: flying entities need its own navigator that randomly flies around
        //  while still keeping its target within line of sight
        return new EntityNavigator($this);
    }

    public function createProjectile(Location $location): ?Entity
    {
        $fireball = new Fireball($location, $this);
        $fireball->setMotion($fireball->getDirectionVector()->multiply(1.5));

        return $fireball;
    }

    public function prepareAttack(): void
    {
        $this->getWorld()->addSound($this->getLocation(), new GhastSound());
    }

    public function getLaunchSound(): Sound
    {
        return new GhastShootSound();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(4.0, 4.0);
    }
}
