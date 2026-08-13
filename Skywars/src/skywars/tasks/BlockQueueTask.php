<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace skywars\tasks;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\World;

class BlockQueueTask extends Task
{
    /** @var Selection[] */
    private array $queue = [];
    /** @var Server */
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function onRun(): void
    {
        $worldManager = $this->getServer()->getWorldManager();

        foreach ($this->queue as $worldId => $selection) {
            if (($world = $worldManager->getWorld($worldId)) !== null) {
                AsyncBlockManager::executeSet($selection, $world);
            }

            unset($this->queue[$worldId]);
        }
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Add blocks to multi-threaded queue.
     *
     * @param Vector3[] $vectors
     * @param Block $block
     * @param World $world
     */
    public function add(array $vectors, Block $block, World $world): void
    {
        $worldId = $world->getId();

        if (!isset($this->queue[$worldId])) {
            $this->queue[$worldId] = new Selection();
        }

        foreach ($vectors as $vector) {
            if ($vector->getY() >= 0) {
                $this->queue[$worldId]->addBlock($vector, $block);
            }
        }
    }
}