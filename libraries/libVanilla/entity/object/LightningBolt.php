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
 * @author CortexPE
 *
 */
declare(strict_types=1);


namespace libVanilla\entity\object;

use libVanilla\event\weather\LightningStrikeEvent;
use libVanilla\sound\ThunderSound;
use pocketmine\block\Block;
use pocketmine\block\TNT;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

// todo: lightning rods
class LightningBolt extends Entity
{
    private const DAMAGE_RADIUS = 3;
    private const MAX_LIFETIME = 4 * 10; // lightning strikes only have up to 4 "passes"

    public static function summon(Position $position): bool
    {
        $world = $position->getWorld();
        $ev = new LightningStrikeEvent($world, $position, 5, $world->getServer()->getDifficulty() >= World::DIFFICULTY_NORMAL);
        $ev->call();
        if ($ev->isCancelled()) {
            return false;
        }
        $entity = new LightningBolt(Location::fromObject($position, $world), $ev->getBaseDamage(), $ev->isPriming());
        $entity->spawnToAll();
        return true;
    }

    public function __construct(Location $location, private float $baseDamage, private bool $priming, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0, 0);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.02;
    }

    protected function getInitialGravity(): float
    {
        return 0.05;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::LIGHTNING_BOLT;
    }

    public function getName(): string
    {
        return "LightningBolt";
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    public function setOnFire(int $seconds): void
    {
        // noop
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() !== EntityDamageEvent::CAUSE_VOID) {
            return;
        }
        parent::attack($source);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isClosed()) {
            return false;
        }
        if ($this->ticksLived > self::MAX_LIFETIME) {
            $this->flagForDespawn();
        }
        if ($this->ticksLived > 0) {
            return parent::entityBaseTick($tickDiff);
        }

        $this->broadcastSound(new ThunderSound());
        $this->broadcastSound(new ExplodeSound());

        $world = $this->location->world;
        $bb = $this->getBoundingBox();

        if ($this->priming) {
            foreach ($world->getCollisionBlocks($bb->expandedCopy(2, 2, 2)) as $collidingBlock) {
                $this->attemptIgnite($collidingBlock);
            }
        }

        foreach ($world->getCollidingEntities($bb->expandedCopy(self::DAMAGE_RADIUS, self::DAMAGE_RADIUS * 2, self::DAMAGE_RADIUS)) as $collidingEntity) {
            if ($collidingEntity->getPosition()->distance($this->location) > self::DAMAGE_RADIUS) {
                continue;
            }

            // todo: mob effects

            $attackEv = new EntityDamageEvent($collidingEntity, EntityDamageEvent::CAUSE_CUSTOM /* todo */, $this->baseDamage);
            $collidingEntity->attack($attackEv);
            if (!$attackEv->isCancelled()) {
                $collidingEntity->setOnFire(8);
            }
        }

        return parent::entityBaseTick($tickDiff);
    }

    private function attemptIgnite(Block $block): void
    {
        if ($block instanceof TNT) {
            $block->ignite();
            return;
        }
        // todo: activate portals

        $sides = iterator_to_array($block->getAllSides());
        shuffle($sides);
        /** @var Block $side */
        foreach ($sides as $side) {
            if (!$side->hasSameTypeId(VanillaBlocks::AIR())) continue;
            $this->location->world->setBlock($side->getPosition(), VanillaBlocks::FIRE());
            return;
        }
    }
}