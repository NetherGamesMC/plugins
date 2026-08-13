<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */

namespace mommasays\utils\entity;

use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class FloatingItem extends ItemEntity
{
    public function __construct(Location $location, Item $item, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $item, $nbt);

        $this->setCanSaveWithChunk(false);
        $this->setNoClientPredictions(true);
        $this->setHasGravity(false);
    }

    protected function getInitialGravity() : float{ return 0; }

    protected function getInitialDragMultiplier() : float{ return 0; }

    public static function getFrom(Location $location, Item $item): self
    {
        return new FloatingItem($location, $item);
    }

    public function onCollideWithPlayer(Player $player): void
    {

    }
}