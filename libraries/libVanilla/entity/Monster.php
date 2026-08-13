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

namespace libVanilla\entity;

use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\ai\navigator\EntityNavigator;
use libVanilla\entity\ai\navigator\pathfinding\AStarPathFindingAlgorithm;
use libVanilla\entity\ai\navigator\pathfinding\DistanceFunction;
use libVanilla\entity\ai\navigator\pathfinding\neighbor\DefaultNeighborResolver;
use libVanilla\entity\ai\navigator\PathFindingNavigator;
use libVanilla\entity\ai\state\AttackingState;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;

abstract class Monster extends EntityBase implements AIEntity
{
    public const ATTACK_COOLDOWN = 20;

    /** @var int */
    protected int $attackCooldownUntil = 0;

    /** @var float[] */
    private array $minDamage = [0.0, 0.0, 0.0, 0.0];
    /** @var float[] */
    private array $maxDamage = [0.0, 0.0, 0.0, 0.0];

    public function isInteresting(Entity $entity): bool
    {
        return ($entity instanceof Player && $entity->isSurvival()) || ($entity instanceof Monster && $entity !== $this);
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $this->isInteresting($source->getDamager())) {
            $this->setTargetEntity($source->getDamager());
        }
    }

    public function getResultDamage(int $difficulty = -1): float
    {
        $damages = $this->getDamages($difficulty);
        return $damages[0] === $damages[1] ? $damages[0] : $damages[0] + Utils::getRandomFloat() * ($damages[1] - $damages[0]);
    }

    /**
     * @param int $difficulty
     *
     * @return float[]
     */
    public function getDamages(int $difficulty = -1): array
    {
        return [$this->getMinDamage($difficulty), $this->getMaxDamage($difficulty)];
    }

    public function getMinDamage(int $difficulty = -1): float
    {
        if ($difficulty > 3 || $difficulty < 0) {
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return $this->minDamage[$difficulty];
    }

    public function getMaxDamage(int $difficulty = -1): float
    {
        if ($difficulty > 3 || $difficulty < 0) {
            $difficulty = Server::getInstance()->getDifficulty();
        }

        return $this->maxDamage[$difficulty];
    }

    public function resetAttack(): void
    {

    }

    public function onAttackTick(): void
    {
        if ($this->getRemainingAttackCooldown() > 0) {
            return;
        }

        $this->interactTarget();
        $this->attackCooldownUntil = $this->location->world->getServer()->getTick() + static::ATTACK_COOLDOWN;
    }

    public function getRemainingAttackCooldown(): int
    {
        return max(0, $this->attackCooldownUntil - $this->location->world->getServer()->getTick());
    }

    public function interactTarget(): void
    {

    }

    public function prepareAttack(): void
    {

    }

    /**
     * @param float[] $damages
     */
    public function setDamages(array $damages): void
    {
        $this->setMinDamages($damages);
    }

    /**
     * @param float[] $damages
     */
    public function setMinDamages(array $damages): void
    {
        foreach ($damages as $i => $damage) {
            $this->minDamage[$i] = $damage;
        }
    }

    /**
     * @param float[] $damages
     */
    public function setMaxDamages(array $damages): void
    {
        foreach ($damages as $i => $damage) {
            $this->maxDamage[$i] = $damage;
        }
    }

    public function getDefaultNavigator(): EntityNavigator
    {
        return new PathFindingNavigator(
            $this,
            new AStarPathFindingAlgorithm(
                DistanceFunction::euclideanSquared(...),
                DistanceFunction::manhattan(...),
                new DefaultNeighborResolver((int)(3 + floor($this->getMaxHealth() / 2)), $this),
                16
            )
        );
    }

    public function setTargetEntity(?Entity $target): void
    {
        parent::setTargetEntity($target);
        if ($target !== null) {
            $this->setState(new AttackingState($this));
        }
    }
}