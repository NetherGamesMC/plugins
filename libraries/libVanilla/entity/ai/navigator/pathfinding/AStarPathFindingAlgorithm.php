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

use Closure;
use libVanilla\entity\ai\navigator\dumper\NodeDumper;
use libVanilla\entity\ai\navigator\pathfinding\neighbor\NeighborResolver;
use libVanilla\entity\ai\navigator\pathfinding\path\NavigationPath;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\ReversePriorityQueue;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use SplPriorityQueue;

/**
 * Slightly modified to allow for partial path-finding (break and backtrack once max depth is reached)
 */
class AStarPathFindingAlgorithm implements PathFindingAlgorithm
{
    /** @var ReversePriorityQueue<float, Vector3> */
    private ReversePriorityQueue $visitQueue;
    /** @var array<int, int> */
    private array $previousNodes = [];
    /** @var array<int, array<int, NavigationPath>> */
    private static array $samePaths = [];

    /**
     * @param Closure(Vector3, Vector3): float $heuristicFunction
     * @param Closure(Vector3, Vector3): float $distanceFunction
     */
    public function __construct(
        protected Closure          $heuristicFunction,
        protected Closure          $distanceFunction,
        protected NeighborResolver $neighborResolver,
        protected int              $maxDepth = 32,
        protected ?NodeDumper      $dumper = null
    )
    {
        $validSignature = fn(Vector3 $a, Vector3 $b): float => 0.0;
        Utils::validateCallableSignature($validSignature, $heuristicFunction);
        Utils::validateCallableSignature($validSignature, $distanceFunction);
    }

    protected function init(): void
    {
        $this->visitQueue = new ReversePriorityQueue();
        $this->visitQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $this->previousNodes = [];
    }

    public function search(World $world, Vector3 $start, Vector3 $end): ?NavigationPath
    {
        // Check if the position is in the world, discard if true. WE ONLY CHECK THIS BECAUSE vec3toHash FUNCTION
        // DOES NOT ALLOW INFINITE NEGATIVE VALUES.
        if (!$world->isInWorld($start->getX(), $start->getY(), $start->getZ()) || !$world->isInWorld($end->getX(), $end->getY(), $end->getZ())) {
            return null;
        }

        $this->init();

        $this->visitQueue->insert($start, 0);

        $heuristic = fn(Vector3 $node) => ($this->heuristicFunction)($node, $end);

        /** @var non-empty-array<int, float> $nodeCost */
        $nodeCost = [PathFindingUtils::vec3toHash($start) => 0.0];

        $worldId = $world->getId();
        if (!isset(self::$samePaths[$worldId])) {
            self::$samePaths[$worldId] = [];
            $world->addOnUnloadCallback(function () use ($world): void {
                unset(self::$samePaths[$world->getId()]);
            });
        }

        $depth = 0;
        while (!$this->visitQueue->isEmpty() && $depth++ < $this->maxDepth) {
            /** @var Vector3 $current */
            $current = $this->visitQueue->current();
            $this->dumper?->showNode($world, $current);

            if ($current->equals($end) || $depth === $this->maxDepth) {
                return $this->backtrack($world, $current);
            }

            $this->visitQueue->next();

            $currentHash = PathFindingUtils::vec3toHash($current);
            foreach ($this->neighborResolver->findNeighbors($world, $current) as $neighbor) {
                // todo: maybe bundle node vec3 + weight into another class? this will do for now
                $extraCost = 0;
                if (is_array($neighbor)) {
                    [$neighbor, $extraCost] = $neighbor;
                }
                $extraCost += count($world->getCollidingEntities(new AxisAlignedBB(
                        $neighbor->x, $neighbor->y, $neighbor->z,
                        $neighbor->x + 1, $neighbor->y + 2, $neighbor->z + 1,
                    ))) ** 2;

                $neighborHash = PathFindingUtils::vec3toHash($neighbor);
                $unfinishedExistingPaths = self::$samePaths[$worldId][$neighborHash] = array_filter(
                    self::$samePaths[$worldId][$neighborHash] ?? [],
                    fn(NavigationPath $path) => !$path->isFinished()
                );
                if (count($unfinishedExistingPaths) > 0) {
                    $extraCost += array_sum(array_map(
                            fn(NavigationPath $path) => $path->nodeCount(),
                            $unfinishedExistingPaths)) / count($unfinishedExistingPaths);
                }

                if (isset($nodeCost[$neighborHash])) {
                    continue; // we've been here before
                }

                $costToNeighbor = $nodeCost[$currentHash] + ($this->distanceFunction)($current, $neighbor) + $extraCost;
                if ($costToNeighbor >= ($nodeCost[$neighborHash] ?? INF)) {
                    continue;
                }

                $this->previousNodes[$neighborHash] = $currentHash;
                $nodeCost[$neighborHash] = $costToNeighbor;

                $this->visitQueue->insert($neighbor, $costToNeighbor + $heuristic($neighbor));
            }
        }
        return null;
    }

    protected function backtrack(World $world, Vector3 $current): NavigationPath
    {
        $pathHashList = [];
        $currentHash = PathFindingUtils::vec3toHash($current);
        $totalPath = [$current];
        while (isset($this->previousNodes[$currentHash])) {
            $currentHash = $this->previousNodes[$currentHash];
            $totalPath[] = PathFindingUtils::hashToVec3($currentHash);
            $pathHashList[] = $currentHash;
        }
        // since we are practically back-tracking, we need to reverse the array to return a path from the beginning
        $path = NavigationPath::fromNodes(array_reverse($totalPath));
        $worldId = $world->getId();
        foreach ($pathHashList as $hash) {
            /** @phpstan-ignore-next-line */
            self::$samePaths[$worldId][$hash][] = $path;
        }
        return $path;
    }
}