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

use SplQueue;

final class NavigationPath
{
    /** @var SplQueue<PathNode> */
    private SplQueue $path;

    public function __construct()
    {
        $this->path = new SplQueue();
    }

    /**
     * @param Vector3[] $nodes
     */
    public static function fromNodes(array $nodes): self
    {
        $instance = new self();
        foreach ($nodes as $node) {
            $instance->add(new PathNode($node));
        }
        return $instance;
    }

    public function add(PathNode $node): void
    {
        $this->path->enqueue($node);
    }

    public function peek(): PathNode
    {
        return $this->path->bottom();
    }

    public function next(): PathNode
    {
        return $this->path->dequeue();
    }

    public function isFinished(): bool
    {
        return $this->path->isEmpty();
    }

    /**
     * @return Vector3[]
     */
    public function toPositionArray(): array
    {
        $path = [];
        for ($i = 0; $i < $this->path->count(); $i++) {
            $path[] = $this->path->offsetGet($i)->getPosition();
        }
        return $path;
    }

    public function nodeCount(): int
    {
        return $this->path->count();
    }
}