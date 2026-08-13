<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace conquests\utils\entity\mob;

use conquests\Conquests;
use conquests\CQArena;
use conquests\CQTeam;
use InvalidArgumentException;
use libVanilla\entity\ai\AIEntityTrait;
use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Monster;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function in_array;
use function round;

abstract class BaseMob extends Monster
{
    use WalkEntityTrait;
    use AIEntityTrait {
        AIEntityTrait::entityBaseTick as private aiTraitBaseTick;
    }

    public const LOSE_DISTANCE = 1024; // 32 blocks

    /** @var CQTeam */
    public CQTeam $team;
    /** @var int */
    public int $lifeDuration;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setNameTag($this->getName());
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();

        $this->setCanSaveWithChunk(false);
    }

    public function getTeam(): CQTeam
    {
        return $this->team;
    }

    protected function setTeam(CQTeam $team): void
    {
        $this->team = $team;
        $this->team->addSpawnedMob($this->getNetworkTypeId());
    }

    public function getArena(): CQArena
    {
        return $this->team->getArena();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if (--$this->lifeDuration <= 0) {
            $this->flagForDespawn();
            return false;
        }

        $nametag = $this->getDynamicNameTag();
        if ($this->getNameTag() !== $nametag) {
            $this->setNameTag($nametag);
        }

        return $this->aiTraitBaseTick($tickDiff);
    }

    protected function onDispose(): void
    {
        parent::onDispose();
        $this->team->removeSpawnedMob($this->getNetworkTypeId());
    }

    protected function destroyCycles(): void
    {
        unset($this->team);
    }

    protected function getDynamicNameTag(): string
    {
        $hearts = '';
        $health = $this->getHealth();
        $maxHealth = $this->getMaxHealth();
        $heartValue = $maxHealth / 10;

        for ($i = $heartValue; $i <= $maxHealth; $i += $heartValue) {
            $hearts .= ($i <= $health ? $this->team->getColor() : TextFormat::GRAY) . ' ■';
        }

        return $this->team->getColor() . round($this->lifeDuration / 20, 1) . 's ' . TextFormat::DARK_GRAY . '[' . $hearts . TextFormat::DARK_GRAY . ' ]';
    }

    public function isInteresting(Entity $entity): bool
    {
        return $entity instanceof Player && !$entity->isClosed() && $entity->canBeCollidedWith() && !in_array($entity, $this->getTeam()->getAlivePlayers(), true);
    }

    public function getFollowDistance(): float
    {
        return self::LOSE_DISTANCE;
    }

    public function interactTarget(): void
    {
        /** @var Entity $target */
        $target = $this->getTargetEntity();

        $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
        $target->attack($ev);

        $this->broadcastAnimation(new ArmSwingAnimation($this));
    }

    public function setOwningEntity(?Entity $owner): void
    {
        parent::setOwningEntity($owner);

        if ($owner instanceof Player) {
            /** @var CQArena|null $arena */
            $arena = Conquests::getInstance()->getArenaByWorld($this->getWorld());

            if ($arena === null) {
                throw new InvalidArgumentException('Arena not found.');
            }

            $this->setTeam($arena->getTeam($owner));
        } else {
            throw new InvalidArgumentException('Owner must be a player.');
        }
    }
}
