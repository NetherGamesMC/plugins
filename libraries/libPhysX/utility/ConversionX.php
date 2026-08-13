<?php
/**
 *   _ _ _     _____  _              __   __
 *  | (_) |   |  __ \| |             \ \ / /
 *  | |_| |__ | |__) | |__  _   _ ___ \ V /
 *  | | | '_ \|  ___/| '_ \| | | / __| > <
 *  | | | |_) | |    | | | | |_| \__ \/ . \
 *  |_|_|_.__/|_|    |_| |_|\__, |___/_/ \_\
 *                           __/ |
 *                          |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * libPhysX was meant to be a stripped version of Nvidia's PhysX Physics Engine, developed purely
 * for the use cases of NetherGames and its systems. In no way shape or form is libPhysX related
 * to Nvidia's PhysX except for the logical understanding between the systems.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace libPhysX\utility;

/**
 * Class ConversionX
 * @package libPhysX\utility
 */
class ConversionX
{

    public const FLOAT_WHOLE = 0;
    public const FLOAT_DECIMAL = 1;

    /**
     * Convert radian to degrees.
     * It's automatically multiplied by "M_PI", no need to do it beforehand.
     *
     * @param float $rad
     * @return float
     */
    public static function convertRadToDegree(float $rad): float
    {
        return rad2deg($rad * M_PI);
    }

    /**
     * Split a float into the whole and decimal part.
     * Set returnWithoutSign to false if you want the sign: -1.25 => [-1.0, -0.25].
     *
     * @param float $number
     * @param bool $returnWithoutSign
     * @return float[]
     */
    public static function splitFloat(float $number, bool $returnWithoutSign = true): array
    {
        $multiplier = 1;
        if ($number < 0) {
            $multiplier = -1;
            $number *= -1;
        }
        $returnArray = [];
        if ($returnWithoutSign === true) {
            $returnArray[self::FLOAT_WHOLE] = floor($number);
        } else {
            $returnArray[self::FLOAT_WHOLE] = floor($number) * $multiplier;
        }
        $returnArray[self::FLOAT_DECIMAL] = $number - $returnArray[self::FLOAT_WHOLE];
        return $returnArray;
    }

}