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
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class LlamaPet extends Animal implements IPetEntity
{
    public const TAG_VARIANT = 'Variant';

    public const TYPE_CREAMY = 0;
    public const TYPE_WHITE = 1;
    public const TYPE_BROWN = 2;
    public const TYPE_GRAY = 3;

    public const LLAMA_TYPES = [
        self::TYPE_CREAMY,
        self::TYPE_WHITE,
        self::TYPE_BROWN,
        self::TYPE_GRAY
    ];

    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::LLAMA;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 2.4, 0);
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
        return array_rand(self::LLAMA_TYPES);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.87, 0.9);
    }
}