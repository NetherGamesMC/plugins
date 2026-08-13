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
use libVanilla\entity\traits\ItemInventoryTrait;
use libVanilla\entity\traits\ProjectileFireTrait;
use libVanilla\entity\utils\EntitySizeUtils;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\sound\BowShootSound;
use pocketmine\world\sound\Sound;
use function intdiv;
use function mt_rand;

class Skeleton extends Monster
{
    use WalkEntityTrait, ItemInventoryTrait, ProjectileFireTrait;

    public const ATTACK_COOLDOWN = 30;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setMaxHealth(20);
        $this->setHealth(20);

        $this->setSpeed(1.2);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SKELETON;
    }

    public function getName(): string
    {
        return 'Skeleton';
    }

    public function getInteractDistance(): float
    {
        return 10;
    }

    public function getDefaultHeldItem(): Item
    {
        return VanillaItems::BOW();
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaItems::BONE()->setCount(mt_rand(0, 2)),
            VanillaItems::ARROW()->setCount(mt_rand(0, 2))
        ];
    }

    public function createProjectile(Location $location): ?Entity
    {
        $bow = $this->getInventory()->getItemInHand();

        if ($bow instanceof Bow) {
            $arrow = new Arrow($location, $this, false);
            $arrow->setPickupMode(Arrow::PICKUP_NONE);
            $arrow->setMotion($this->getDirectionVector());

            if (($punchLevel = $bow->getEnchantmentLevel(VanillaEnchantments::PUNCH())) > 0) {
                $arrow->setPunchKnockback($punchLevel);
            }
            if (($powerLevel = $bow->getEnchantmentLevel(VanillaEnchantments::POWER())) > 0) {
                $arrow->setBaseDamage($arrow->getBaseDamage() + (($powerLevel + 1) / 2));
            }
            if ($bow->hasEnchantment(VanillaEnchantments::FLAME())) {
                $arrow->setOnFire(intdiv($arrow->getFireTicks(), 20) + 100);
            }

            $ev = new EntityShootBowEvent($this, $bow, $arrow, 3.375);
            $ev->call();

            $arrow = $ev->getProjectile(); //This might have been changed by plugins

            if ($ev->isCancelled()) {
                $arrow->flagForDespawn();

                return null;
            }

            $arrow->setMotion($arrow->getMotion()->multiply($ev->getForce()));

            return $arrow;
        }

        return null;
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return 5;
        }

        return 0;
    }

    public function getLaunchSound(): Sound
    {
        return new BowShootSound();
    }

    public function getDefaultItem(): Item
    {
        return VanillaItems::BOW();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return EntitySizeUtils::upright(1.9, 0.6);
    }
}
