<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\ai\navigator\pathfinding;

use pocketmine\math\Vector3;

final class DistanceFunction
{
    final private function __construct()
    {
    }

    public static function euclideanExact(Vector3 $a, Vector3 $b): float
    {
        return $a->distance($b);
    }

    public static function euclideanSquared(Vector3 $a, Vector3 $b): float
    {
        return $a->distanceSquared($b);
    }

    public static function manhattan(Vector3 $a, Vector3 $b): float
    {
        $abs = $a->subtractVector($b)->abs();
        return $abs->x + $abs->y + $abs->z;
    }
}