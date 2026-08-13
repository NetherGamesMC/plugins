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

namespace libVanilla\entity\inventory;

use libVanilla\entity\Monster;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;

class MonsterInventory extends SimpleInventory
{

    public function __construct(protected Monster $holder, int $size = 1)
    {
        parent::__construct($size);
    }

    public function getItemInHand(): Item
    {
        return $this->getItem(0);
    }

    public function setItemInHand(Item $item): void
    {
        $this->setItem(0, $item);
    }

    public function getHolder(): Monster
    {
        return $this->holder;
    }

    public function getName(): string
    {
        return 'MonsterInventory';
    }
}