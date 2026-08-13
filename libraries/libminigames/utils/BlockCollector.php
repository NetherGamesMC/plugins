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


namespace libminigames\utils;


use pocketmine\math\Vector3;
use pocketmine\world\World;

class BlockCollector
{
    /** @var array<int, true> */
    private array $blocks = [];

    public function addBlock(Vector3 $pos): void
    {
        $this->blocks[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())] = true;
    }

    public function removeBlock(Vector3 $pos): void
    {
        unset($this->blocks[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())]);
    }

    /**
     * @return array<int, true>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function isBreakable(Vector3 $pos): bool
    {
        return isset($this->blocks[World::blockHash($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())]);
    }
}