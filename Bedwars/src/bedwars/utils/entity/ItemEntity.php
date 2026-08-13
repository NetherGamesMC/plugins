<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
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

namespace bedwars\utils\entity;

use bedwars\generators\Generator;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class ItemEntity extends \pocketmine\entity\object\ItemEntity
{
    private bool $canDuplicate = false;

    public function isMergeable(\pocketmine\entity\object\ItemEntity $entity): bool
    {
        return $entity instanceof self && parent::isMergeable($entity) && $entity->canDuplicate() === $this->canDuplicate();
    }

    public function canDuplicate(): bool
    {
        return $this->canDuplicate;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setByte("CanDuplicate", $this->canDuplicate ? 1 : 0);

        return $nbt;
    }

    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->getPickupDelay() !== 0) {
            return;
        }

        parent::onCollideWithPlayer($player);
        Generator::onGeneratorItemPickup($player, $this->getItem());

        if ($this->canDuplicate) {
            $location = $this->getLocation();
            $bb = AxisAlignedBB::one()->expand(2, 2, 2)->offset($location->x, $location->y, $location->z);
            $entities = $this->getWorld()->getCollidingEntities($bb);

            foreach ($entities as $p) {
                if ($p instanceof Player) {
                    if ($p === $player) {
                        continue;
                    }

                    parent::onCollideWithPlayer($p);
                    Generator::onGeneratorItemPickup($p, $this->getItem());
                }
            }
        }
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setCanDuplicate($nbt->getByte("CanDuplicate", 0) !== 0);
    }

    public function setCanDuplicate(bool $canDuplicate = true): void
    {
        $this->canDuplicate = $canDuplicate;
    }
}