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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils\skins;

use pocketmine\entity\Skin;
use function ord;
use function round;
use function strlen;

class SkinValidator
{
    public const SKIN_64_32 = 64 * 32 * 4;
    public const SKIN_64_64 = 64 * 64 * 4;
    public const SKIN_128_64 = 128 * 64 * 4;
    public const SKIN_128_128 = 128 * 128 * 4;

    public const BOUNDS_64_64 = 0;
    public const BOUNDS_128_128 = 1;

    public const ALLOWED_TRANSPARENCY = 8;

    /** @var array */
    private array $skinBounds;

    public function __construct()
    {
        $cubes = [
            ["x" => 8, "y" => 12, "z" => 4, "uvX" => 16, "uvY" => 16],
            ["x" => 8, "y" => 8, "z" => 8, "uvX" => 0, "uvY" => 0],
            ["x" => 4, "y" => 12, "z" => 4, "uvX" => 40, "uvY" => 16],
            ["x" => 4, "y" => 12, "z" => 4, "uvX" => 32, "uvY" => 48],
            ["x" => 4, "y" => 12, "z" => 4, "uvX" => 0, "uvY" => 16],
            ["x" => 4, "y" => 12, "z" => 4, "uvX" => 16, "uvY" => 48]
        ];
        $this->skinBounds = [
            self::BOUNDS_64_64 => $this->getSkinBounds($cubes),
            self::BOUNDS_128_128 => $this->getSkinBounds($cubes, 2.0)
        ];
    }

    private function getSkinBounds(array $cubes, float $scale = 1.0): array
    {
        $bounds = [];

        foreach ($cubes as $cube) {
            $x = (int)($scale * $cube['x']);
            $y = (int)($scale * $cube['y']);
            $z = (int)($scale * $cube['z']);
            $uvX = (int)($scale * $cube['uvX']);
            $uvY = (int)($scale * $cube['uvY']);
            $bounds[] = [
                'min' => ['x' => $uvX + $z, 'y' => $uvY],
                'max' => ['x' => $uvX + $z + (2 * $x) - 1, 'y' => $uvY + $z - 1]
            ];
            $bounds[] = [
                'min' => ['x' => $uvX, 'y' => $uvY + $z],
                'max' => ['x' => $uvX + (2 * ($z + $x)) - 1, 'y' => $uvY + $z + $y - 1]
            ];
        }

        return $bounds;
    }

    public function validateSkin(Skin $skin): bool
    {
        return $this->getSkinTransparencyPercentage($skin->getSkinData()) <= self::ALLOWED_TRANSPARENCY;
    }

    private function getSkinTransparencyPercentage(string $skinData): int
    {
        $len = strlen($skinData);
        if (($bounds = $this->getBoundsForLength($len)) === null) {
            return -1;
        }

        [$maxX, $maxY] = self::getSkinDataSize($len);
        $total = $transparent = 0;

        foreach ($bounds as $bound) {
            if ($bound['max']['x'] > $maxX || $bound['max']['y'] > $maxY) {
                continue;
            }

            for ($y = $bound['min']['y']; $y <= $bound['max']['y']; $y++) {
                for ($x = $bound['min']['x']; $x <= $bound['max']['x']; $x++) {
                    $key = (($maxX * $y) + $x) * 4;

                    if (ord($skinData[$key + 3]) < 127) {
                        $transparent++;
                    }

                    $total++;
                }
            }
        }

        return (int)round($transparent * 100 / $total);
    }

    private function getBoundsForLength(int $len): ?array
    {
        return match ($len) {
            self::SKIN_64_32, self::SKIN_64_64 => $this->skinBounds[self::BOUNDS_64_64],
            self::SKIN_128_64, self::SKIN_128_128 => $this->skinBounds[self::BOUNDS_128_128],
            default => null,
        };
    }

    public static function getSkinDataSize(int $skinDataLength): array
    {
        return match ($skinDataLength) {
            self::SKIN_64_32 => [64, 32],
            self::SKIN_64_64 => [64, 64],
            self::SKIN_128_64 => [128, 64],
            self::SKIN_128_128 => [128, 128],
            default => [0, 0],
        };
    }
}