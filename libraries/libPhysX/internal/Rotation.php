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
 * Class Rotation
 * @package libPhysX\internal
 */
class Rotation
{

    /** @var float */
    public float $yaw;
    /** @var float */
    public float $pitch;

    /**
     * Rotation constructor.
     * @param float $yaw
     * @param float $pitch
     */
    public function __construct(float $yaw = 0.0, float $pitch = 0.0)
    {
        $this->yaw = $yaw;
        $this->pitch = $pitch;
    }

}