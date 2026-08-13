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
 * @author Drew, Driesboy, CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\entity;


use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\ai\state\EvadingState;
use libVanilla\entity\ai\state\FollowingState;
use libVanilla\entity\traits\BabyTrait;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Animal extends EntityBase implements AIEntity, Breedable
{
    use BabyTrait;

    public function getInteractDistance(): float
    {
        return 1.5;
    }

    public function isFoodItem(Item $item): bool
    {
        // todo: pretty sure it makes more sense if the "breedable" code depended on the "food item" part,
        //  not the other way around
        return in_array($item->getTypeId(), $this->getBreedingItems(), true);
    }

    public function isInteresting(Entity $entity): bool
    {
        return $entity instanceof Player && $this->isFoodItem($entity->getInventory()->getItemInHand());
    }

    public function setTargetEntity(?Entity $target): void
    {
        parent::setTargetEntity($target);
        if ($target !== null) {
            $this->setState(new FollowingState($this));
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager === null) {
                return;
            }
            $this->setState(new EvadingState($this, $damager));
        }
    }
}