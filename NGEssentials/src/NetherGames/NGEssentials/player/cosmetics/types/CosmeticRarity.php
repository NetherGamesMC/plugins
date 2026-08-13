<?php

namespace NetherGames\NGEssentials\player\cosmetics\types;

use pocketmine\utils\TextFormat;

enum CosmeticRarity: int
{
    case COMMON = 0;
    case UNCOMMON = 1;
    case RARE = 2;
    case EPIC = 3;
    case LEGENDARY = 4;

    public function getName(): string
    {
        return match ($this) {
            self::COMMON => 'Common',
            self::UNCOMMON => 'Uncommon',
            self::RARE => 'Rare',
            self::EPIC => 'Epic',
            self::LEGENDARY => 'Legendary',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::COMMON => TextFormat::GRAY,
            self::UNCOMMON => TextFormat::GREEN,
            self::RARE => TextFormat::AQUA,
            self::EPIC => TextFormat::LIGHT_PURPLE,
            self::LEGENDARY => TextFormat::GOLD,
        };
    }
}