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

namespace NetherGames\NGEssentials\utils;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\tasks\PingDbTask;
use NetherGames\NGEssentials\thread\NGThreadPool;
use pocketmine\scheduler\Task;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\result\SqlChangeResult;
use poggit\libasynql\result\SqlInsertResult;
use poggit\libasynql\result\SqlSelectResult;
use poggit\libasynql\SqlError;
use poggit\libasynql\SqlThread;
use function in_array;
use function microtime;

class MySQLCredentials
{
    /** @var DataConnector|null */
    private static ?DataConnector $dataConnector = null;
    /** @var NGEssentials */
    private static NGEssentials $plugin;
    /** @var array */
    private static array $credentials;

    public function __construct(NGEssentials $plugin, array $credentials)
    {
        self::$plugin = $plugin;
        self::$credentials = $credentials;

        try {
            [$host, $username, $password, $schema] = $credentials;

            self::$dataConnector = libasynql::create($plugin, [
                'type' => 'mysql',
                'mysql' => [
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'schema' => $schema
                ],
                'worker-limit' => 8
            ], [
                'mysql' => 'mysql.sql'
            ], NGEssentials::isInDevelopmentMode());

            $plugin->getLogger()->info('§aMySQL Database connection established successfully!');
        } catch (SqlError $exception) {
            $plugin->getLogger()->info('§cMySQL Database connection failed: ' . $exception->getMessage());
        }
    }

    public static function executeChange(string $queryName, array $args = [], ?callable $onSuccess = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplLast($queryName, $args, SqlThread::MODE_CHANGE, static function (SqlChangeResult $result) use ($onSuccess, $queryName, $startTime): void {
                if ($onSuccess !== null) {
                    $onSuccess($result->getAffectedRows());
                }

                self::calculateQueryDuration($queryName, $startTime);
            }, self::onErrorWrapper($onError, $queryName, $startTime));
            return;
        }

        self::onDatabaseOffline($queryName, $args, $onError);
    }

    public static function isDatabaseOnline(): bool
    {
        return self::$dataConnector !== null;
    }

    public static function getMySQLDatabase(): DataConnector
    {
        return self::$dataConnector;
    }

    private static function calculateQueryDuration(string $queryName, float $startTime, ?SqlError $error = null): void
    {
        $duration = microtime(true) - $startTime;

        $logger = self::getPlugin()->getLogger();
        if ($error === null) {
            if ($duration > 20) {
                $logger->error('§c' . $queryName . ' took ' . $duration . ' seconds to execute!');
            } else {
                $logger->debug("Query $queryName executed in $duration seconds");
            }
        } else {
            $logger->emergency("Query $queryName failed in $duration seconds: " . $error->getErrorMessage());
        }
    }

    public static function getPlugin(): NGEssentials
    {
        return self::$plugin;
    }

    private static function onErrorWrapper(?callable $handler, string $query, float $startTime): callable
    {
        return static function (SqlError $result) use ($handler, $query, $startTime): void {
            if ($handler !== null) {
                $handler($result);
            }

            if ($result->getStage() === SqlError::STAGE_CONNECT || ($result->getStage() === SqlError::STAGE_EXECUTE && in_array($result->getErrorMessage(), ['Connection was killed', 'MySQL server has gone away'], true))) {
                self::setOffline();
            }

            self::calculateQueryDuration($query, $startTime, $result);
        };
    }

    private static function setOffline(): void
    {
        if (self::$dataConnector !== null) {
            self::$dataConnector = null;
            self::$plugin->getLogger()->emergency('§cMySQL Database connection lost!');

            self::getPlugin()->getScheduler()->scheduleRepeatingTask(new class(self::$credentials) extends Task {
                /** @var array */
                private array $credentials;

                public function __construct(array $credentials)
                {
                    $this->credentials = $credentials;
                }

                public function onRun(): void
                {
                    if (MySQLCredentials::isDatabaseOnline()) {
                        $this->getHandler()->cancel();
                    } else {
                        NGThreadPool::getInstance()->submitTask(new PingDbTask($this->credentials));
                    }
                }
            }, 60 * 20);
        }
    }

    private static function onDatabaseOffline(string $query, array $args, ?callable $onError): void
    {
        if ($onError !== null) {
            $onError(new SqlError(SqlError::STAGE_EXECUTE, "MySQL server has gone away", $query, $args));
        }
    }

    public static function executeGenericRaw(string $query, array $args = [], ?callable $onSuccess = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplRaw([$query], [$args], [SqlThread::MODE_GENERIC], static function () use ($onSuccess, $query, $startTime) {
                if ($onSuccess !== null) {
                    $onSuccess();
                }

                self::calculateQueryDuration($query, $startTime);
            }, self::onErrorWrapper($onError, $query, $startTime));
            return;
        }

        self::onDatabaseOffline($query, $args, $onError);
    }

    public static function executeChangeRaw(string $query, array $args = [], ?callable $onSuccess = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplRaw([$query], [$args], [SqlThread::MODE_CHANGE], static function (array $results) use ($onSuccess, $query, $startTime) {
                if ($onSuccess !== null) {
                    /** @var SqlChangeResult $result */
                    $result = $results[0];
                    $onSuccess($result->getAffectedRows());
                }

                self::calculateQueryDuration($query, $startTime);
            }, self::onErrorWrapper($onError, $query, $startTime));
            return;
        }

        self::onDatabaseOffline($query, $args, $onError);
    }

    public static function executeInsert(string $queryName, array $args = [], ?callable $onInserted = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplLast($queryName, $args, SqlThread::MODE_INSERT, static function (SqlInsertResult $result) use ($onInserted, $queryName, $startTime) {
                if ($onInserted !== null) {
                    $onInserted($result->getInsertId(), $result->getAffectedRows());
                }

                self::calculateQueryDuration($queryName, $startTime);
            }, self::onErrorWrapper($onError, $queryName, $startTime));
            return;
        }

        self::onDatabaseOffline($queryName, $args, $onError);
    }

    public static function executeInsertRaw(string $query, array $args = [], ?callable $onInserted = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplRaw([$query], [$args], [SqlThread::MODE_INSERT], static function (array $results) use ($onInserted, $query, $startTime) {
                if ($onInserted !== null) {
                    /** @var SqlInsertResult $result */
                    $result = $results[0];
                    $onInserted($result->getInsertId());
                }

                self::calculateQueryDuration($query, $startTime);
            }, self::onErrorWrapper($onError, $query, $startTime));
            return;
        }

        self::onDatabaseOffline($query, $args, $onError);
    }

    public static function executeGeneric(string $queryName, array $args = [], ?callable $onSuccess = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplLast($queryName, $args, SqlThread::MODE_GENERIC, static function () use ($onSuccess, $queryName, $startTime) {
                if ($onSuccess !== null) {
                    $onSuccess();
                }

                self::calculateQueryDuration($queryName, $startTime);
            }, self::onErrorWrapper($onError, $queryName, $startTime));
            return;
        }

        self::onDatabaseOffline($queryName, $args, $onError);
    }

    public static function executeSelect(string $queryName, array $args = [], ?callable $onSelect = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplLast($queryName, $args, SqlThread::MODE_SELECT, static function (SqlSelectResult $result) use ($onSelect, $queryName, $startTime) {
                if ($onSelect !== null) {
                    $onSelect($result->getRows(), $result->getColumnInfo());
                }

                self::calculateQueryDuration($queryName, $startTime);
            }, self::onErrorWrapper($onError, $queryName, $startTime));
            return;
        }

        self::onDatabaseOffline($queryName, $args, $onError);
    }

    public static function executeSelectRaw(string $query, array $args = [], ?callable $onSelect = null, ?callable $onError = null): void
    {
        if (self::isDatabaseOnline()) {
            $startTime = microtime(true);

            self::getMySQLDatabase()->executeImplRaw([$query], [$args], [SqlThread::MODE_SELECT], static function (array $results) use ($onSelect, $query, $startTime) {
                if ($onSelect !== null) {
                    /** @var SqlSelectResult $result */
                    $result = $results[0];
                    $onSelect($result->getRows());
                }

                self::calculateQueryDuration($query, $startTime);
            }, self::onErrorWrapper($onError, $query, $startTime));
            return;
        }

        self::onDatabaseOffline($query, $args, $onError);
    }

    public static function close(): void
    {
        if (self::isDatabaseOnline()) {
            $plugin = self::getPlugin();
            $logger = $plugin->getLogger();
            $logger->info("Closing database connection");
            if (!$plugin->getServer()->hasForceShutdown()) {
                $logger->info("Waiting for all queries to be executed");
                self::getMySQLDatabase()->waitAll();
                self::getMySQLDatabase()->close();
            }
            self::$dataConnector = null;
            $logger->info("Database connection closed");
        }
    }
}