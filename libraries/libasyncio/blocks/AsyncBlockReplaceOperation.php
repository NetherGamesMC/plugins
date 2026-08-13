<?php
/**
 *   _ _ _                                _
 *  | (_) |                              (_)
 *  | |_| |__   __ _ ___ _   _ _ __   ___ _  ___
 *  | | | '_ \ / _` / __| | | | '_ \ / __| |/ _ \
 *  | | | |_) | (_| \__ \ |_| | | | | (__| | (_) |
 *  |_|_|_.__/ \__,_|___/\__, |_| |_|\___|_|\___/
 *                        __/ |
 *                       |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author driesboy
 *
 */
declare(strict_types=1);

namespace libasyncio\blocks;

use Closure;
use pocketmine\block\Block;
use pocketmine\world\World;
use function array_map;
use function in_array;

class AsyncBlockReplaceOperation extends AsyncBlockOperation
{
    /** @var string */
    private string $replaceIds;

    /**
     * @param Selection $selection
     * @param Block[] $replaces
     * @param World $world
     * @param Closure|null $closure
     */
    public function __construct(Selection $selection, array $replaces, World $world, ?Closure $closure = null)
    {
        $this->replaceIds = self::serialize(array_map(static function (Block $block) {
            return $block->getStateId();
        }, $replaces));

        parent::__construct($selection, $world, $closure);
    }

    public function onRun(): void
    {
        $manager = $this->makeChunkManager();
        $replaceIds = $this->getReplaceIds();
        $replayBlocks = [];

        foreach ($this->getBlocksByChunk() as $chunkHash => $blocksByChunk) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            /** @var Chunk $chunk */
            $chunk = $manager->getChunk($chunkX, $chunkZ);

            foreach ($blocksByChunk as $blockHash => $fullId) {
                World::getBlockXYZ($blockHash, $x, $y, $z);

                $saveX = $x & 0xf;
                $saveZ = $z & 0xf;

                if (in_array($chunk->getBlockStateId($saveX, $y, $saveZ), $replaceIds, true)) {
                    $chunk->setBlockStateId($saveX, $y, $saveZ, $fullId);
                    $replayBlocks[$blockHash] = $fullId;
                }
            }
        }

        $this->saveChunkManager($manager);

        if ($this->hasReplay()) {
            $this->replayBlocks = self::serialize($replayBlocks);
        }
    }

    /**
     * @return int[]
     */
    public function getReplaceIds(): array
    {
        /** @var int[] $replaceIds */
        $replaceIds = self::unserialize($this->replaceIds);

        return $replaceIds;
    }
}