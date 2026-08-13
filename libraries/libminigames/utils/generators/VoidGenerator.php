<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);


namespace libminigames\utils\generators;


use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;

class VoidGenerator extends Generator
{
    private Chunk $chunk;

    /**
     * @param int $seed
     */
    public function __construct(int $seed)
    {
        parent::__construct($seed, '');
        $this->generateBaseChunk();
    }

    protected function generateBaseChunk(): void
    {
        $this->chunk = new Chunk([], true);
    }

    public function getName(): string
    {
        return "void";
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $world->setChunk($chunkX, $chunkZ, clone $this->chunk);
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
    }
}
