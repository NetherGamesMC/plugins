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

namespace libVanilla\entity\ai\navigator\pathfinding\listener;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class PathFindingClosureChunkListener extends PathFindingChunkListener
{
    /**
     * @param Closure(int, int): void $onChunkInvalidate
     * @param Closure(Vector3): void $onNodeInvalidate
     */
    public function __construct(World $world, private Closure $onChunkInvalidate, private Closure $onNodeInvalidate)
    {
        parent::__construct($world);
        Utils::validateCallableSignature(function (int $chunkX, int $chunkZ): void {
        }, $onChunkInvalidate);
        Utils::validateCallableSignature(function (Vector3 $pos): void {
        }, $onNodeInvalidate);
    }

    protected function onChunkInvalidate(int $chunkX, int $chunkZ): void
    {
        ($this->onChunkInvalidate)($chunkX, $chunkZ);
    }

    protected function onNodeInvalidate(Vector3 $pos): void
    {
        ($this->onNodeInvalidate)($pos);
    }
}