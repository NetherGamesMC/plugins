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
use pocketmine\world\World;

final class PathFindingUtils
{
    private function __construct()
    {
    }

    public static function vec3toHash(Vector3 $v): int
    {
        return World::blockHash($v->x, $v->y, $v->z);
    }

    public static function hashToVec3(int $hash): Vector3
    {
        $v = Vector3::zero();
        World::getBlockXYZ($hash, $v->x, $v->y, $v->z);

        return $v;
    }

}