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

use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class PathFindingNeighborCacheManager
{
    use SingletonTrait;

    /** @var array<int, array<string, PathFindingNeighborCache>> */
    private array $instances = [];

    public function get(World $world, string $entityClassName): PathFindingNeighborCache
    {
        $index = $world->getId();
        if (!isset($this->instances[$index])) {
            $this->instances[$index] = [];
            $world->addOnUnloadCallback(function () use ($index): void {
                foreach ($this->instances[$index] as $entityClassName => $_) {
                    unset($this->instances[$index][$entityClassName]);
                }
            });
        }
        if (!isset($this->instances[$index][$entityClassName])) {
            $this->instances[$index][$entityClassName] = new PathFindingNeighborCache($world);
        }
        return $this->instances[$index][$entityClassName];
    }
}