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
 * @author k3ithos, matcracker, Drew, driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\passive\Pig;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

class PigPet extends Pig implements IPetEntity
{
    use WalkingPetTrait;

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 1.8, 0);
    }

    protected function onMount(Entity $rider): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SADDLED, true);
    }

    protected function onUnmount(Entity $rider): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SADDLED, false);
    }
}