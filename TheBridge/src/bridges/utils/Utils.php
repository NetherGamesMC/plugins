<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges\utils;

use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use function array_search;
use function sqrt;

class Utils extends \libminigames\utils\Utils
{
    /** @var int[][] */
    public const TEXTFORMAT_RGB = [
        [0, 0, 0],
        [0, 0, 170],
        [0, 170, 0],
        [0, 170, 170],
        [170, 0, 0],
        [170, 0, 170],
        [255, 170, 0],
        [170, 170, 170],
        [85, 85, 85],
        [85, 85, 255],
        [85, 255, 85],
        [85, 255, 255],
        [255, 85, 85],
        [255, 85, 255],
        [255, 255, 85],
        [255, 255, 255]
    ];

    /** @var string[] */
    public const TEXTFORMAT_LIST = [
        TextFormat::BLACK,
        TextFormat::DARK_BLUE,
        TextFormat::DARK_GREEN,
        TextFormat::DARK_AQUA,
        TextFormat::DARK_RED,
        TextFormat::DARK_PURPLE,
        TextFormat::GOLD,
        TextFormat::GRAY,
        TextFormat::DARK_GRAY,
        TextFormat::BLUE,
        TextFormat::GREEN,
        TextFormat::AQUA,
        TextFormat::RED,
        TextFormat::LIGHT_PURPLE,
        TextFormat::YELLOW,
        TextFormat::WHITE
    ];

    /**
     * @param string $colorCode
     * @return int[]
     */
    public static function textFormatToRGB(string $colorCode): array
    {
        return self::TEXTFORMAT_RGB[array_search($colorCode, self::TEXTFORMAT_LIST, true)];
    }

    public static function getXZDistance(Vector3 $vector, Vector3 $pos): float
    {
        return sqrt(($vector->x - $pos->x) ** 2 + ($vector->z - $pos->z) ** 2);
    }
}