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

use bedwars\utils\entity\mob\Bedbug;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\player\Player;

class Snowball extends \NetherGames\NGEssentials\entity\Snowball
{
    public function __construct(Location $location, ?Entity $shootingEntity)
    {
        parent::__construct($location, $shootingEntity);

        $this->setCanSaveWithChunk(false);
    }

    public function onHit(ProjectileHitEvent $event): void
    {
        $player = $this->getOwningEntity();
        if ($player instanceof Player && $player->getWorld() === $this->getWorld()) {
            $mob = new Bedbug($this->getLocation(), null);
            $mob->setOwningEntity($player);
            $mob->spawnToAll();
        }
    }
}