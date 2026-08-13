<?php

namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\Monster;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class EndermanPet extends Monster implements IPetEntity
{
    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDERMAN;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 3.75, 0);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.9, 0.6);
    }
}