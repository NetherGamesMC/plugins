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

namespace conquests\utils\entity;

use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class PrimedTNT extends \pocketmine\entity\object\PrimedTNT
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->setCanSaveWithChunk(false);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($hasUpdate && $this->getFuse() % 2 === 0) {
            $this->setNameTag(TextFormat::RED . round($this->getFuse() / 20, 1));
        }

        return $hasUpdate;
    }
}