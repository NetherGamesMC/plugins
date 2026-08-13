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

namespace libVanilla\entity\ai\navigator\pathfinding\path;

use libPhysX\internal\Rotation;
use pocketmine\math\Vector3;

class PathNode
{
    public function __construct(private Vector3 $position, private ?Rotation $rotation = null)
    {
    }

    public function getPosition(): Vector3
    {
        return $this->position;
    }

    public function getRotation(): ?Rotation
    {
        // unused, for future applications such as scripted or interpolated entity movement
        return $this->rotation;
    }
}