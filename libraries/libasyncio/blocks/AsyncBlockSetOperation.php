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

use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class AsyncBlockSetOperation extends AsyncBlockOperation
{
    public function onRun(): void
    {
        $manager = $this->makeChunkManager();
        $replayBlocks = [];

        foreach ($this->getBlocksByChunk() as $chunkHash => $blocksByChunk) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            /** @var Chunk $chunk */
            $chunk = $manager->getChunk($chunkX, $chunkZ);

            foreach ($blocksByChunk as $blockHash => $fullId) {
                World::getBlockXYZ($blockHash, $x, $y, $z);

                $chunk->setBlockStateId($x & 0xf, $y, $z & 0xf, $fullId);
                $replayBlocks[$blockHash] = $fullId;
            }
        }

        $this->saveChunkManager($manager);

        if ($this->hasReplay()) {
            $this->replayBlocks = self::serialize($replayBlocks);
        }
    }
}