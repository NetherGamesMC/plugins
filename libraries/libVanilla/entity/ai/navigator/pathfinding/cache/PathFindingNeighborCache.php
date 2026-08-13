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

namespace libVanilla\entity\ai\navigator\pathfinding\cache;

use libVanilla\entity\ai\navigator\pathfinding\listener\PathFindingClosureChunkListener;
use libVanilla\entity\ai\navigator\pathfinding\PathFindingUtils;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class PathFindingNeighborCache
{
    /** @var array<int, array<int,float|Vector3>> */
    private array $neighborCache = []; // nodeHash => [neighbors ...]
    /** @var array<int, int> */
    private array $parentCache = []; // neighborHash => parentHash - this is to make sure the parents get invalidated as well
    /** @var array<int, array<int, bool>> */
    private array $nodeMap = []; // chunkHash => [nodeHash ...]
    private PathFindingClosureChunkListener $listener;

    public function __construct(private World $world)
    {
        $this->listener = new PathFindingClosureChunkListener(
            $world,
            $this->invalidateChunk(...),
            $this->invalidateBlock(...)
        );
    }

    public function __destruct()
    {
        foreach ($this->nodeMap as $chunkHash => $nodes) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $this->world->unregisterChunkListener($this->listener, $chunkX, $chunkZ);
        }
    }

    /**
     * @return array<int, float|Vector3>|null
     */
    public function retrieve(Vector3 $node): ?array
    {
        return $this->neighborCache[PathFindingUtils::vec3toHash($node)] ?? null;
    }

    private function invalidateParents(int $nodeHash): void
    {
        $lastHash = $nodeHash;
        while (isset($this->parentCache[$lastHash])) {
            $toUnset = $lastHash;
            $lastHash = $this->parentCache[$toUnset];
            unset($this->parentCache[$toUnset], $this->neighborCache[$nodeHash]);
        }
    }

    private function invalidateChunk(int $chunkX, int $chunkZ): void
    {
        $chunkHash = World::chunkHash($chunkX, $chunkZ);
        if (!isset($this->nodeMap[$chunkHash])) {
            return;
        }
        foreach ($this->nodeMap[$chunkHash] as $nodeHash => $_) {
            $this->invalidateParents($nodeHash);
        }
        unset($this->nodeMap[$chunkHash]);
    }

    private function invalidateBlock(Vector3 $pos): void
    {
        $chunkX = $pos->x >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $pos->z >> Chunk::COORD_BIT_SIZE;

        $nodeHash = PathFindingUtils::vec3toHash($pos);
        unset(
            $this->nodeMap[World::chunkHash($chunkX, $chunkZ)][$nodeHash],
            $this->neighborCache[$nodeHash]
        );
        $this->invalidateParents($nodeHash);
    }

    /**
     * @param Vector3|array<int,float|Vector3> ...$neighbors
     */
    public function indexNeighbors(Vector3 $node, ...$neighbors): void
    {
        $chunkHashes = [];
        foreach ($neighbors as $neighbor) {
            $neighborPos = $neighbor;
            if (is_array($neighbor)) {
                $neighborPos = $neighborPos[0];
            }
            $currentHash = PathFindingUtils::vec3toHash($node);
            $neighborHash = PathFindingUtils::vec3toHash($neighborPos);
            $chunkHash = World::chunkHash(
                $node->getFloorX() >> Chunk::COORD_BIT_SIZE,
                $node->getFloorZ() >> Chunk::COORD_BIT_SIZE
            );

            $chunkHashes[$chunkHash] = true; // make a unique list without using array_unique
            $this->nodeMap[$chunkHash][$currentHash] = true;
            $this->parentCache[$neighborHash] = $currentHash;
            $this->neighborCache[$currentHash][$neighborHash] = $neighbor;
        }

        foreach ($chunkHashes as $chunkHash => $i) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);
            $this->listener->listen($chunkX, $chunkZ);
        }
    }
}