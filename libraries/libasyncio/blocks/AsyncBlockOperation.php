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
use InvalidArgumentException;
use libReplay\session\record\RecordManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\SimpleChunkManager;
use pocketmine\world\World;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_callable;

abstract class AsyncBlockOperation extends AsyncTask
{
    /** @var string */
    protected string $replayBlocks;
    /** @var string */
    private string $blocksByChunk;
    /** @var string */
    private string $serializedChunks;
    /** @var int */
    private int $worldId;
    /** @var int */
    private int $minY;
    /** @var int */
    private int $maxY;
    /** @var bool */
    private bool $recording = false;

    /**
     * AsyncBlockOperation constructor.
     *
     * @param Selection $selection
     * @param World $world
     * @param Closure|null $closure
     */
    public function __construct(Selection $selection, World $world, ?Closure $closure = null)
    {
        if ($closure !== null) {
            $this->storeLocal('closure', $closure);
        }

        if (($recordManager = RecordManager::getInstance()) !== null) {
            $this->recording = $recordManager->getRecording($world) !== null;
        }

        $this->setSelection($world, $selection);
        $this->setWorld($world);
    }

    private function setSelection(World $world, Selection $selection): void
    {
        $serializedChunks = [];
        $blocksByChunks = [];
        $blocks = $selection->getBlocks();

        foreach ($blocks as $blockHash => $fullId) {
            World::getBlockXYZ($blockHash, $x, $y, $z);

            $blocksByChunks[World::chunkHash($x >> 4, $z >> 4)][$blockHash] = $fullId;
        }

        foreach ($blocksByChunks as $chunkHash => $blocksByChunk) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            $chunk = $world->loadChunk($chunkX, $chunkZ);

            if ($chunk === null) {
                unset($blocksByChunks[$chunkHash]);
            } else {
                $serializedChunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
            }
        }

        $this->blocksByChunk = self::serialize($blocksByChunks);
        $this->serializedChunks = self::serialize($serializedChunks);
    }

    protected static function serialize(array $unserialized): string
    {
        /** @var string $serialize */
        $serialize = igbinary_serialize($unserialized);

        return $serialize;
    }

    private function setWorld(World $world): void
    {
        $this->worldId = $world->getId();
        $this->maxY = $world->getMaxY();
        $this->minY = $world->getMinY();
    }

    /**
     * @return array<int, array<int, int>> (chunkHash => [blockHash => fullId, ...], ...)
     */
    public function getBlocksByChunk(): array
    {
        /** @var array<int, array<int, int>> $blocksByChunk (chunkHash => [blockHash => fullId, ...], ...) */
        $blocksByChunk = self::unserialize($this->blocksByChunk);

        return $blocksByChunk;
    }

    /**
     * @return array<int, int> (blockHash => fullId, ...)
     */
    public function getReplayBlocks(): array
    {
        /** @var array<int, int> $replayBlocks (blockHash => fullId, ...) */
        $replayBlocks = self::unserialize($this->replayBlocks);

        return $replayBlocks;
    }

    /**
     * @return array<int, string> (chunkHash => serializedChunk, ...)
     */
    public function getSerializedChunks(): array
    {
        /** @var array<int, string> $serializedChunks (chunkHash => serializedChunk, ...) */
        $serializedChunks = self::unserialize($this->serializedChunks);

        return $serializedChunks;
    }

    /**
     * @param string $serialized
     */
    protected static function unserialize(string $serialized): array
    {
        /** @var array $unserialize */
        $unserialize = igbinary_unserialize($serialized);

        return $unserialize;
    }

    public function onCompletion(): void
    {
        if (($world = Server::getInstance()->getWorldManager()->getWorld($this->worldId)) !== null) {
            if ($this->hasReplay() && ($recordManager = RecordManager::getInstance()) !== null && ($recording = $recordManager->getRecording($world)) !== null) {
                $selection = new Selection($this->getReplayBlocks());
                $recording->getCamera()->getFilmroll()->onBlocksChange($selection);
            }

            foreach ($this->getSerializedChunks() as $chunkHash => $serializedChunk) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);

                $world->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($serializedChunk));
            }
        }

        try {
            $action = $this->fetchLocal('closure');
        } catch (InvalidArgumentException $exception) {
            $action = null;
        }

        if (is_callable($action)) {
            $action();
        }
    }

    public function hasReplay(): bool
    {
        return $this->recording;
    }

    protected function makeChunkManager(): SimpleChunkManager
    {
        $manager = new SimpleChunkManager($this->minY, $this->maxY);

        foreach ($this->getSerializedChunks() as $chunkHash => $serialized_chunk) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            $manager->setChunk($chunkX, $chunkZ, FastChunkSerializer::deserializeTerrain($serialized_chunk));
        }

        return $manager;
    }

    protected function saveChunkManager(SimpleChunkManager $manager): void
    {
        $serializedChunks = [];

        foreach ($this->getSerializedChunks() as $chunkHash => $unused) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            /** @var Chunk $chunk */
            $chunk = $manager->getChunk($chunkX, $chunkZ);
            $serializedChunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }

        $this->serializedChunks = self::serialize($serializedChunks);
    }
}