<?php

declare(strict_types=1);

namespace skywars\entities;

use libVanilla\entity\passive\Sheep;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

class DinnerboneSheep extends Sheep
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setCanSaveWithChunk(false);
    }

    public function onUpdate(int $currentTick): bool
    {
        parent::onUpdate($currentTick);
        return true;
    }
}