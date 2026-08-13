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

use NetherGames\NGEssentials\thread\NGThreadPool;
use pmmp\thread\Thread as NativeThread;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use RuntimeException;
use function is_file;
use function pocketmine\critical_error;

class Autoloader extends AsyncTask
{
    public function __construct(private string $bootstrap)
    {
    }

    public static function initAutoloader(string $bootstrap): void
    {
        if (is_file($bootstrap)) {
            require_once($bootstrap);

            if (NativeThread::getCurrentThread() === null) { // check if we are in the main thread
                $serverPool = Server::getInstance()->getAsyncPool();
                $serverPool->addWorkerStartHook(function (int $workerId) use ($bootstrap, $serverPool): void {
                    $serverPool->submitTaskToWorker(new Autoloader($bootstrap), $workerId);
                });

                $serverPool = NGThreadPool::getInstance();
                $serverPool->addWorkerStartHook(function (int $workerId) use ($bootstrap, $serverPool): void {
                    $serverPool->submitTaskToWorker(new Autoloader($bootstrap), $workerId);
                });
            }
        } else {
            critical_error("Composer autoloader not found at " . $bootstrap);
            critical_error("Please install/update Composer dependencies or use provided builds.");

            throw new RuntimeException("No composer autoloader were found.");
        }
    }

    public function onRun(): void
    {
        self::initAutoloader($this->bootstrap);
    }
}