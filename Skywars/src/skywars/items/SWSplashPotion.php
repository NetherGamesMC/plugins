<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, xBeastMode
 *
 */
declare(strict_types=1);

namespace skywars\items;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\Item;
use pocketmine\item\SplashPotion;
use pocketmine\player\Player;
use skywars\entities\SWSplashPotion as SWSplashPotionEntity;

class SWSplashPotion extends SplashPotion
{
    /** @var EffectInstance[] */
    private array $effectInstances = [];

    /**
     * @param EffectInstance $effectInstance
     *
     * @return Item
     */
    public function addEffect(EffectInstance $effectInstance): Item
    {
        $this->effectInstances[] = $effectInstance;
        return $this;
    }

    public function createEntity(Location $location, Player $thrower): Throwable
    {
        return new SWSplashPotionEntity($location, $thrower, $this->getType(), null, $this->effectInstances);
    }
}