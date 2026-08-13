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
 * @author k3ithos, matcracker, driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\ai\AIEntity;
use libVanilla\entity\Breedable;
use libVanilla\entity\EntityBase;
use libVanilla\entity\traits\BabyTrait;
use NetherGames\NGEssentials\entity\CustomGeometryTrait;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use NetherGames\NGEssentials\entity\pets\PetEntityTrait;
use NetherGames\NGEssentials\utils\skins\SkinStore;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\utils\Utils;

class CompanionPet extends EntityBase implements AIEntity, Breedable, IPetEntity
{
    use WalkingPetTrait, BabyTrait, PetEntityTrait, CustomGeometryTrait {
        WalkingPetTrait::entityBaseTick insteadof BabyTrait;
        PetEntityTrait::onInteract insteadof BabyTrait;
        BabyTrait::initEntity as protected initBabyTrait;
        PetEntityTrait::initEntity insteadof BabyTrait;
        CustomGeometryTrait::sendSpawnPacket insteadof WalkingPetTrait;
        CustomGeometryTrait::sendSpawnPacket insteadof PetEntityTrait;
    }

    public function getSkin(): Skin
    {
        $owner = $this->getOwningEntityInWorld();
        if (!$owner instanceof Human) {
            return SkinStore::getInstance()->lazyLoad("default", Utils::getRandomFloat() < 0.5 ? "steve" : "alex", "CompanionPet_default");
        }
        return $owner->getSkin();
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 0.5, -0.25);
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->initBabyTrait($nbt);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6, 1.62);
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        $this->setScale(0.5);
        parent::syncNetworkData($properties);
    }
}