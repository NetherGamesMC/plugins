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


namespace NetherGames\NGEssentials\entity\pets\hovering;

use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class BeePet extends Animal implements IPetEntity
{
    use HoveringPetTrait;

    /*
    use CustomGeometryTrait;
    public function getSkin(): Skin { return SkinStore::getInstance()->lazyLoad("pets", "bee", "BeePet", "geometry.pets.bee"); }
    protected function getInitialSizeInfo(): EntitySizeInfo { return new EntitySizeInfo(0.15, 0.15); }
    */

    public static function getNetworkTypeId(): string
    {
        return EntityIds::BEE;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 1.5, 0);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.6, 0.7);
    }
}