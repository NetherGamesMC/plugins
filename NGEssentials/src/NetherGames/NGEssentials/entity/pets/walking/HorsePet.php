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

class HorsePet extends Animal implements IPetEntity
{
    public const TAG_VARIANT = 'Variant';

    public const TYPE_NONE = 0;
    public const TYPE_WHITE = 1;
    public const TYPE_WHITE_FIELD = 2;
    public const TYPE_WHITE_DOTS = 3;
    public const TYPE_BLACK_DOTS = 4;

    public const HORSE_TYPES = [
        self::TYPE_NONE,
        self::TYPE_WHITE,
        self::TYPE_WHITE_FIELD,
        self::TYPE_WHITE_DOTS,
        self::TYPE_BLACK_DOTS
    ];

    public const COLOR_WHITE = 0;
    public const COLOR_CREAMY = 1;
    public const COLOR_CHESTNUT = 2;
    public const COLOR_BROWN = 3;
    public const COLOR_BLACK = 4;
    public const COLOR_GRAY = 5;
    public const COLOR_DARKBROWN = 6;

    public const HORSE_COLORS = [
        self::COLOR_WHITE,
        self::COLOR_CREAMY,
        self::COLOR_CHESTNUT,
        self::COLOR_BROWN,
        self::COLOR_BLACK,
        self::COLOR_GRAY,
        self::COLOR_DARKBROWN
    ];

    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::HORSE;
    }

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 2.3, 0);
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->setVariant($nbt->getShort(self::TAG_VARIANT, self::createVariant($this->getRandomType(), $this->getRandomColor())));
    }

    public function setVariant(int $variant): void
    {
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::VARIANT, $variant);
    }

    public static function createVariant(int $type, int $colour): int
    {
        return $colour | $type << 8;
    }

    public function getRandomType(): int
    {
        return array_rand(self::HORSE_TYPES);
    }

    public function getRandomColor(): int
    {
        return array_rand(self::HORSE_COLORS);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.6, 1.4);
    }
}