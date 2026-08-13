<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class ChickenPet extends Animal implements IPetEntity
{
    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    public function petEntityBaseTick(int $tickDiff): bool
    {
        if (!$this->onGround && $this->motion->y < 0) {
            $this->motion->y *= 0.6;
        }
        return true;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 1.8, 0);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.8, 0.6);
    }
}