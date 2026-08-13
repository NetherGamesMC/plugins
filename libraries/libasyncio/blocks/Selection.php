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


use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function array_replace;

class Selection
{
    /** @var array<int, int> */
    private array $blocks;

    /**
     * @param array<int, int> $blocks
     */
    public function __construct(array $blocks = [])
    {
        $this->blocks = $blocks;
    }

    public function addBlock(Vector3 $pos, Block $block): void
    {
        $this->add((int)$pos->getX(), (int)$pos->getY(), (int)$pos->getZ(), $block);
    }

    public function add(int $x, int $y, int $z, Block $block): void
    {
        $this->addRaw(World::blockHash($x, $y, $z), $block->getStateId());
    }

    public function addRaw(int $blockHash, int $stateId): void
    {
        $this->blocks[$blockHash] = $stateId;
    }

    public function addSelection(Selection $selection): void
    {
        $this->blocks = array_replace($this->blocks, $selection->getBlocks());
    }

    /**
     * @return array<int, int>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}