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

namespace libPhysX\internal;

/**
 * Class RayInterference
 * @package libPhysX\internal
 */
class RayInterference
{

    /** @var float */
    private float $x;
    /** @var float */
    private float $y;
    /** @var float */
    private float $z;

    /**
     * RayInterference constructor.
     * @param float $dX
     * @param float $dY
     * @param float $dZ
     * @param float $radiusX
     * @param float $radiusY
     * @param float $radiusZ
     */
    public function __construct(float $dX, float $dY, float $dZ, float $radiusX, float $radiusY, float $radiusZ)
    {
        $this->x = $radiusX;
        $this->y = $radiusY;
        $this->z = $radiusZ;
        // technically we shouldn't be rounding but the delta is negligible so we do it anyways.
        $conditionX = (int)round($dX) === 0;
        $conditionY = (int)round($dY) === 0;
        $conditionZ = (int)round($dZ) === 0;
        if ($conditionY === false && $conditionZ === false) {
            $this->x = (($radiusX / $dY) + ($radiusX / $dZ)) / 2;
        } else if ($conditionY === true && $conditionZ === false) {
            $this->x = $radiusX / $dZ;
        } else if ($conditionY === false) {
            $this->x = $radiusX / $dY;
        }
        if ($conditionX === false && $conditionZ === false) {
            $this->y = (($radiusY / $dX) + ($radiusY / $dZ)) / 2;
        } else if ($conditionX === true && $conditionZ === false) {
            $this->y = $radiusY / $dZ;
        } else if ($conditionX === false) {
            $this->y = $radiusY / $dX;
        }
        if ($conditionX === false && $conditionY === false) {
            $this->z = (($radiusZ / $dX) + ($radiusZ / $dY)) / 2;
        } else if ($conditionX === true && $conditionY === false) {
            $this->z = $radiusZ / $dY;
        } else if ($conditionX === false) {
            $this->z = $radiusZ / $dX;
        }
        $this->x = abs($this->x);
        $this->y = abs($this->y);
        $this->z = abs($this->z);
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return float
     */
    public function getZ(): float
    {
        return $this->z;
    }

}
