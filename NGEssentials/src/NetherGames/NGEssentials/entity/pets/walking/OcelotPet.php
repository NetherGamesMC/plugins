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

use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class OcelotPet extends Animal implements IPetEntity
{

    public const TAG_VARIANT = 'Variant';

    public const TYPE_WILD = 0;
    public const TYPE_TUXEDO = 1;
    public const TYPE_TABBY = 2;
    public const TYPE_SIAMESE = 3;

    public const OCELOT_TYPES = [
        self::TYPE_WILD,
        self::TYPE_TUXEDO,
        self::TYPE_TABBY,
        self::TYPE_SIAMESE
    ];

    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::OCELOT;
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->setVariant($nbt->getInt(self::TAG_VARIANT, $this->getRandomType()));
    }

    public function setVariant(int $variant): void
    {
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $variant);
    }

    public function getRandomType(): int
    {
        return self::OCELOT_TYPES[array_rand(self::OCELOT_TYPES)];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.7, 0.6);
    }

    protected function onMount(Entity $rider): void
    {
        $this->setSpeed(2);
    }

    protected function onUnmount(Entity $rider): void
    {
        $this->setSpeed(1);
    }
}