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


namespace NetherGames\NGEssentials\player\cosmetics\utils\pet;

use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\bouncing\BouncingPetTrait;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\nbt\tag\CompoundTag;

class BouncingAnimal extends Animal implements IPetEntity
{
    use BouncingPetTrait {
        BouncingPetTrait::initPetData as private baseInitPetData;
        BouncingPetTrait::jump as private traitJump;
    }

    use BaseCosmeticPetTrait {
        BaseCosmeticPetTrait::initPetData as private cosmeticInitPetData;
    }

    public function jump(): void
    {
        $this->traitJump();
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->baseInitPetData($nbt);
        $this->cosmeticInitPetData($nbt);
    }
}