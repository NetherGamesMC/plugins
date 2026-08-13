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
use libVanilla\entity\utils\EntitySizeUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class IronGolem extends Monster
{
    use WalkEntityTrait;

    /** @var bool */
    private bool $friendly = false;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(100);
        $this->setHealth(100);

        $this->setSpeed(1.25);
        $this->setMaxDamages([0, 11.75, 21.5, 32.25]);
        $this->setMinDamages([0, 4.75, 7.5, 11.25]);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::IRON_GOLEM;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->friendly = (bool)$nbt->getByte('Friendly', 0);
    }

    public function getName(): string
    {
        return 'Iron Golem';
    }

    public function isInteresting(Entity $entity): bool
    {
        if ($this->isFriendly()) {
            return $entity instanceof Monster && !$entity instanceof self;
        }

        return parent::isInteresting($entity);
    }

    public function isFriendly(): bool
    {
        return $this->friendly;
    }

    public function setFriendly(bool $value): void
    {
        $this->friendly = $value;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human && $this->isFriendly()) {
            $this->setFriendly(false);
        }
    }

    public function interactTarget(): void
    {
        /** @var Entity $target */
        $target = $this->getTargetEntity();
        if ($target instanceof Player) {
            $damage = $this->getResultDamage();
        } else {
            $damage = $this->getResultDamage(2);
        }

        if ($damage >= 0) {
            $this->broadcastAnimation(new ArmSwingAnimation($this));

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $target->attack($ev);

            if (!$ev->isCancelled()) {
                $target->setMotion($target->getMotion()->add(0, 0.45, 0));
            }
        }
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setByte('Friendly', (int)$this->friendly);

        return $nbt;
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaItems::IRON_INGOT()->setCount(mt_rand(3, 5)),
            VanillaBlocks::POPPY()->asItem()->setCount(mt_rand(0, 2)),
        ];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return EntitySizeUtils::upright(2.9, 1.4);
    }
}
