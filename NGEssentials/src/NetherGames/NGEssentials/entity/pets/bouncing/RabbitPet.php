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


namespace NetherGames\NGEssentials\entity\pets\bouncing;

use libVanilla\entity\passive\Rabbit;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use NetherGames\NGEssentials\entity\sound\RabbitHopSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class RabbitPet extends Rabbit implements IPetEntity
{
    use BouncingPetTrait {
        BouncingPetTrait::initPetData as private baseInitPetData;
        BouncingPetTrait::jump as private traitJump;
    }

    // todo: this is a "small creature"

    // todo: move variant stuff to Rabbit base
    public const TYPE_BROWN = 0;
    public const TYPE_WHITE = 1;
    public const TYPE_BLACK = 2;
    public const TYPE_BLACK_AND_WHITE = 3;
    public const TYPE_GOLD = 4;
    public const TYPE_SALT_AND_PEPPER = 5;
    public const TYPE_KILLER_BUNNY = 6;

    public const TAG_VARIANT = 'Variant';

    private int $variant = self::TYPE_BROWN;

    public function jump(): void
    {
        if ($this->onGround) {
            $this->broadcastSound(new RabbitHopSound());
        }
        $this->traitJump();
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->baseInitPetData($nbt);
        $this->setVariant($nbt->getInt(self::TAG_VARIANT, mt_rand(0, 6)));
    }

    public function setVariant(int $variant): void
    {
        $this->variant = $variant;
        $this->networkPropertiesDirty = true;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);
        $properties->setInt(EntityMetadataProperties::VARIANT, $this->variant);
    }
}