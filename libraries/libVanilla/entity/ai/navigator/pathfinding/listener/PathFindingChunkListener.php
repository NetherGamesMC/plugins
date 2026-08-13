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

use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkListenerNoOpTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

abstract class PathFindingChunkListener implements ChunkListener
{
    use ChunkListenerNoOpTrait {
        onChunkChanged as private;
        onBlockChanged as private;
        onChunkUnloaded as private;
    }

    public function __construct(
        protected World $world
    )
    {
    }

    public function listen(int $chunkX, int $chunkZ): void
    {
        $this->world->registerChunkListener($this, $chunkX, $chunkZ);
    }

    /**
     * @internal
     */
    final public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
        $this->onChunkInvalidate($chunkX, $chunkZ);
    }

    /**
     * @internal
     */
    final public function onBlockChanged(Vector3 $block): void
    {
        $this->onNodeInvalidate($block);
    }

    /**
     * @internal
     */
    final public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
        $this->onChunkInvalidate($chunkX, $chunkZ);
        $this->world->unregisterChunkListener($this, $chunkX, $chunkZ);
    }

    abstract protected function onChunkInvalidate(int $chunkX, int $chunkZ): void;

    abstract protected function onNodeInvalidate(Vector3 $pos): void;
}