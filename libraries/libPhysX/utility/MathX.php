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


use pocketmine\math\Vector3;

/**
 * Class MathX
 * @package libPhysX\utility
 */
class MathX
{

    /**
     * Get a horizontal plane circle with X as the ordinate and Z as the abscissa.
     *
     * @param Vector3 $center
     * @param float $radius
     * @return Vector3[]
     */
    public static function calculateXZCircleAtRadius(Vector3 $center, float $radius): array
    {
        $vectorToAdd = [];
        for ($i = 0; $i < 360; $i++) {
            if ($i === 0) {
                $vectorToAdd[] = new Vector3(0, 0, $radius);
                continue;
            }
            if ($i === 90) {
                $vectorToAdd[] = new Vector3($radius, 0, 0);
                continue;
            }
            if ($i === 180) {
                $vectorToAdd[] = new Vector3(0, 0, -$radius);
                continue;
            }
            if ($i === 270) {
                $vectorToAdd[] = new Vector3(-$radius, 0, 0);
                continue;
            }
            $radianI = deg2rad($i);
            $abscissa = $radius * cos($radianI);
            $ordinate = $radius * sin($radianI);
            $vectorToAdd[] = new Vector3($ordinate, 0, $abscissa);
        }
        $returnVector = [];
        foreach ($vectorToAdd as $addVector) {
            $returnVector[] = $center->addVector($addVector);
        }
        return $returnVector;
    }

    /**
     * Get a vertical plane circle with Y as the ordinate and X as the abscissa.
     *
     * @param Vector3 $center
     * @param float $radius
     * @return Vector3[]
     */
    public static function calculateYXCircleAtRadius(Vector3 $center, float $radius): array
    {
        $vectorToAdd = [];
        for ($i = 0; $i < 360; $i++) {
            if ($i === 0) {
                $vectorToAdd[] = new Vector3($radius, 0, 0);
                continue;
            }
            if ($i === 90) {
                $vectorToAdd[] = new Vector3(0, $radius, 0);
                continue;
            }
            if ($i === 180) {
                $vectorToAdd[] = new Vector3(-$radius, 0, 0);
                continue;
            }
            if ($i === 270) {
                $vectorToAdd[] = new Vector3(0, -$radius, 0);
                continue;
            }
            $radianI = deg2rad($i);
            $abscissa = $radius * cos($radianI);
            $ordinate = $radius * sin($radianI);
            $vectorToAdd[] = new Vector3($abscissa, $ordinate, 0);
        }
        $returnVector = [];
        foreach ($vectorToAdd as $addVector) {
            $returnVector[] = $center->addVector($addVector);
        }
        return $returnVector;
    }

    /**
     * Get a vertical plane circle with Y as the ordinate and Z as the abscissa.
     *
     * @param Vector3 $center
     * @param float $radius
     * @return Vector3[]
     */
    public static function calculateYZCircleAtRadius(Vector3 $center, float $radius): array
    {
        $vectorToAdd = [];
        for ($i = 0; $i < 360; $i++) {
            if ($i === 0) {
                $vectorToAdd[] = new Vector3(0, 0, $radius);
                continue;
            }
            if ($i === 90) {
                $vectorToAdd[] = new Vector3(0, $radius, 0);
                continue;
            }
            if ($i === 180) {
                $vectorToAdd[] = new Vector3(0, 0, -$radius);
                continue;
            }
            if ($i === 270) {
                $vectorToAdd[] = new Vector3(0, -$radius, 0);
                continue;
            }
            $radianI = deg2rad($i);
            $abscissa = $radius * cos($radianI);
            $ordinate = $radius * sin($radianI);
            $vectorToAdd[] = new Vector3(0, $ordinate, $abscissa);
        }
        $returnVector = [];
        foreach ($vectorToAdd as $addVector) {
            $returnVector[] = $center->addVector($addVector);
        }
        return $returnVector;
    }

    /**
     * Get a horizontal plane circle with X as the ordinate and Z as the abscissa.
     *
     * This method is an alternative method to the brute-force one
     * using the epsilon-delta definition.
     *
     * @param Vector3 $center
     * @param float $radius
     * @param float $precision
     * @return array
     */
    public static function calculateFilledCircleXZ(Vector3 $center, float $radius, float $precision = 1.0): array
    {
        $circle = [];
        $modulus = $radius ** 2;
        $radiusVector = new Vector3($radius, $radius, $radius);
        $min = $center->subtractVector($radiusVector);
        $max = $center->addVector($radiusVector);
        for ($x = $max->x; $x >= $min->x; $x -= $precision) {
            $deltaX = $x - $center->x;
            $epsilonX = ($deltaX ** 2) / $modulus;
            for ($z = $max->z; $z >= $min->z; $z -= $precision) {
                $deltaZ = $z - $center->z;
                $epsilonZ = ($deltaZ ** 2) / $modulus;
                $epsilonDefinitiveSum = $epsilonX + $epsilonZ;
                if ($epsilonDefinitiveSum <= 1.0) {
                    $circle[] = new Vector3($x, $center->y, $z);
                }
            }
        }
        return $circle;
    }

    /**
     * Rotate a Vector around the X-Axis.
     *
     * @param Vector3 $vector
     * @param float $eulerAngle
     * @return Vector3
     */
    public static function rotateVectorAroundXAxis(Vector3 $vector, float $eulerAngle): Vector3
    {
        $rad = deg2rad($eulerAngle);
        $cosRad = cos($rad);
        $sinRad = sin($rad);
        $yCos = $vector->y * $cosRad;
        $zCos = $vector->z * $cosRad;
        $ySin = $vector->y * $sinRad;
        $zSin = $vector->z * $sinRad;
        $y = $yCos - $zSin;
        $z = $ySin + $zCos;
        return new Vector3($vector->x, $y, $z);
    }

    /**
     * Rotate a Vector around the Y-Axis.
     *
     * @param Vector3 $vector
     * @param float $eulerAngle
     * @return Vector3
     */
    public static function rotateVectorAroundYAxis(Vector3 $vector, float $eulerAngle): Vector3
    {
        $rad = deg2rad($eulerAngle);
        $cosRad = cos($rad);
        $sinRad = sin($rad);
        $xCos = $vector->x * $cosRad;
        $zCos = $vector->z * $cosRad;
        $xSin = $vector->x * $sinRad;
        $zSin = $vector->z * $sinRad;
        $x = $xCos + $zSin;
        $z = $zCos - $xSin;
        return new Vector3($x, $vector->y, $z);
    }

    /**
     * Rotate a Vector around the Z-Axis.
     *
     * @param Vector3 $vector
     * @param float $eulerAngle
     * @return Vector3
     */
    public static function rotateVectorAroundZAxis(Vector3 $vector, float $eulerAngle): Vector3
    {
        $rad = deg2rad($eulerAngle);
        $cosRad = cos($rad);
        $sinRad = sin($rad);
        $xCos = $vector->x * $cosRad;
        $yCos = $vector->y * $cosRad;
        $xSin = $vector->x * $sinRad;
        $ySin = $vector->y * $sinRad;
        $x = $xCos - $ySin;
        $y = $xSin + $yCos;
        return new Vector3($x, $y, $vector->z);
    }

    /**
     * Calculate a Sphere at a certain radius.
     *
     * @param Vector3 $center
     * @param float $radius
     * @param bool $fill
     * @param float $precision
     * @return Vector3[]
     */
    public static function calculateSphere(Vector3 $center, float $radius, bool $fill = true, float $precision = 1.0): array
    {
        $sphere = [];
        $modulus = $radius ** 2;
        $radiusVector = new Vector3($radius, $radius, $radius);
        $min = $center->subtractVector($radiusVector);
        $max = $center->addVector($radiusVector);
        for ($x = $max->x; $x >= $min->x; $x -= $precision) {
            $deltaX = $x - $center->x;
            $epsilonX = ($deltaX ** 2) / $modulus;
            for ($y = $max->y; $y >= $min->y; $y -= $precision) {
                $deltaY = $y - $center->y;
                $epsilonY = ($deltaY ** 2) / $modulus;
                for ($z = $max->z; $z >= $min->z; $z -= $precision) {
                    $deltaZ = $z - $center->z;
                    $epsilonZ = ($deltaZ ** 2) / $modulus;
                    $epsilonDefinitiveSum = $epsilonX + $epsilonY + $epsilonZ;
                    if ($epsilonDefinitiveSum <= 1.0) {
                        if ($fill === false && $epsilonDefinitiveSum < 0.85) {
                            continue;
                        }
                        $sphere[] = new Vector3($x, $y, $z);
                    }
                }
            }
        }
        return $sphere;
    }

    /**
     * Calculate the 2D distance on the X-Z plane.
     *
     * The squared parameter returns distance-squared.
     *
     * @param Vector3 $from
     * @param Vector3 $to
     * @param bool $squared
     * @return float
     */
    public static function calculateXZDistance(Vector3 $from, Vector3 $to, bool $squared = false): float
    {
        $dx = $to->x - $from->x;
        $dz = $to->z - $from->z;
        $distanceSquared = ($dx ** 2) + ($dz ** 2);
        if ($squared) {
            return $distanceSquared;
        }
        return sqrt($distanceSquared);
    }

    /**
     * Calculate the 2D distance on the X-Y plane.
     *
     * The squared parameter returns distance-squared.
     *
     * @param Vector3 $from
     * @param Vector3 $to
     * @param bool $squared
     * @return float
     */
    public static function calculateXYDistance(Vector3 $from, Vector3 $to, bool $squared = false): float
    {
        $dx = $to->x - $from->x;
        $dy = $to->y - $from->y;
        $distanceSquared = ($dx ** 2) + ($dy ** 2);
        if ($squared) {
            return $distanceSquared;
        }
        return sqrt($distanceSquared);
    }

    /**
     * Calculate the 2D distance on the Z-Y plane.
     *
     * The squared parameter returns distance-squared.
     *
     * @param Vector3 $from
     * @param Vector3 $to
     * @param bool $squared
     * @return float
     */
    public static function calculateZYDistance(Vector3 $from, Vector3 $to, bool $squared = false): float
    {
        $dz = $to->z - $from->z;
        $dy = $to->y - $from->y;
        $distanceSquared = ($dz ** 2) + ($dy ** 2);
        if ($squared) {
            return $distanceSquared;
        }
        return sqrt($distanceSquared);
    }

}