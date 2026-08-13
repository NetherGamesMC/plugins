<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\thread;

use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\DumpWorkerMemoryTask;
use pocketmine\scheduler\GarbageCollectionTask;
use pocketmine\snooze\SleeperHandler;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\ThreadSafeClassLoader;
use RuntimeException;
use function gc_collect_cycles;

class NGThreadPool extends AsyncPool
{
    public const NG_MEMORY_LIMIT = 256; // 256MB Limit
    public const NG_POOL_SIZE = 2; // 3 workers

    /** @var NGThreadPool|null */
    private static ?NGThreadPool $instance = null;

    public function __construct(int $size, int $workerMemoryLimit, ThreadSafeClassLoader $classLoader, ThreadSafeLogger $logger, SleeperHandler $eventLoop)
    {
        parent::__construct($size, $workerMemoryLimit, $classLoader, $logger, $eventLoop);
        self::$instance = $this;
    }

    /**
     * @return NGThreadPool
     * @throws RuntimeException
     *
     */
    public static function getInstance(): NGThreadPool
    {
        if (self::$instance === null) {
            throw new RuntimeException("Attempt to retrieve NGThreadPool instance outside server thread");
        }
        return self::$instance;
    }

    /**
     * Dumps the server memory into the specified output folder.
     *
     * @param string $outputFolder
     * @param int $maxNesting
     * @param int $maxStringSize
     * @return void
     */
    public function dumpMemory(string $outputFolder, int $maxNesting, int $maxStringSize): void
    {
        foreach ($this->getRunningWorkers() as $i) {
            $this->submitTaskToWorker(new DumpWorkerMemoryTask($outputFolder, $maxNesting, $maxStringSize), $i);
        }
    }

    public function triggerGarbageCollector(): int
    {
        $this->shutdownUnusedWorkers();

        foreach ($this->getRunningWorkers() as $i) {
            $this->submitTaskToWorker(new GarbageCollectionTask(), $i);
        }

        return gc_collect_cycles();
    }
}