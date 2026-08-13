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
 * @author larryTheCoder
 *
 */
declare(strict_types=1);

namespace libasyncio\s3;

use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\task\S3GetFileObjectTask;
use libasyncio\s3\task\S3GetObjectTask;
use libasyncio\s3\task\S3ObjectBulkDeleteTask;
use libasyncio\s3\task\S3ObjectDeleteTask;
use libasyncio\s3\task\S3ObjectExistsTask;
use libasyncio\s3\task\S3ObjectVersionsTask;
use libasyncio\s3\task\S3UploadFileTask;
use libasyncio\s3\task\S3UploadObjectTask;
use libasynCurl\thread\CurlThreadPool;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

/**
 * AWS S3 simple object storage manager for PocketMine-MP
 */
class S3StorageManager
{
    /** @var CurlThreadPool */
    private CurlThreadPool $threadPool;
    /** @var S3StorageCredentials */
    private S3StorageCredentials $credentials;

    public function __construct(PluginBase $plugin, S3StorageCredentials $credentials, int $workers = CurlThreadPool::POOL_SIZE)
    {
        $server = $plugin->getServer();

        $this->credentials = $credentials;
        $this->threadPool = new CurlThreadPool($workers, CurlThreadPool::MEMORY_LIMIT, $server->getLoader(), $server->getLogger(), $server->getTickSleeper());

        $this->threadPool->addWorkerStartHook(NGEssentials::getInstance()->getStartupHook($this->threadPool));

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->threadPool->collectTasks();
        }), CurlThreadPool::COLLECT_INTERVAL);
        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->threadPool->triggerGarbageCollector();
        }), CurlThreadPool::GARBAGE_COLLECT_INTERVAL);
    }

    /**
     * An AWS S3 bucket operation to retrieve an object and store them a file in the storage. If you want to get
     * the object without having to store them into the disk. Use {@link S3StorageManager::getObject()}.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param string $filePath the path to the disk that is needed to be written on.
     * @param callable $onSuccess a callback function when the operation is successful: <code>function(bool $success) : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function getFileObject(S3ObjectIdentifier $identifier, string $filePath, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3GetFileObjectTask($this->credentials, $onSuccess, $onError))->setFileLocation($filePath)->setObjectIdentifier($identifier));
    }

    /**
     * An AWS S3 bucket operation to retrieve an object content immediately as a parameter.
     * Note: Object size may affect the efficiency of this operation as it requires more memory to transfer
     * the content across threads. Use only if the content is not large.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param callable $onSuccess a callback function with signature: <code>function(?string $content) : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function getObject(S3ObjectIdentifier $identifier, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3GetObjectTask($this->credentials, $onSuccess, $onError))->setObjectIdentifier($identifier));
    }

    /**
     * An AWS S3 bucket operation to place an object from a file.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param string $filePath the path of a file that is needed to be written on the AWS S3 bucket.
     * @param callable $onSuccess a callback function when the operation is successful: <code>function() : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function putFileObject(S3ObjectIdentifier $identifier, string $filePath, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3UploadFileTask($this->credentials, $onSuccess, $onError))->setFileLocation($filePath)->setObjectIdentifier($identifier));
    }

    /**
     * An AWS S3 bucket operation to put an object content immediately from a parameter.
     * Note: Content size may affect the efficiency of this operation as it requires more memory to transfer
     * the content across threads. Use if and only if the content is not larger than 80MB.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param string $contents The contents that would be written in the object.
     * @param callable $onSuccess a callback function with signature: <code>function() : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function putObject(S3ObjectIdentifier $identifier, string $contents, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3UploadObjectTask($this->credentials, $onSuccess, $onError))->setObjectContents($contents)->setObjectIdentifier($identifier));
    }

    /**
     * Delete an object from the AWS S3 bucket. This may return false if the object is no longer exists.
     * Note: To delete multiple objects once, please use {@link S3StorageManager::deleteObjectBulk()}. It is not
     * advisable to delete more than 1 object using this function.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param callable $onSuccess a callback function when the operation is successful: <code>function(bool $success) : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function deleteObject(S3ObjectIdentifier $identifier, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3ObjectDeleteTask($this->credentials, $onSuccess, $onError))->setObjectIdentifier($identifier));
    }

    /**
     * Delete multiple objects from the AWS S3 bucket.
     * Note: This operation can contain a list up to 1000 keys that you want to delete.
     *
     * @param S3ObjectIdentifier[] $identifiers The AWS bucket object identifiers.
     * @param callable $onSuccess a callback function with the list of status for the operation: <code>function(S3DeletedObject[] $objects) : void{}</code>
     *                                          The returned objects is the result for each deletion operation in $objectIdentifiers, it may return not return
     *                                          the object of a certain file if it does not exist. Check Error parameter to see if the operation was unsuccessful
     *                                          for a particular file object.
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function deleteObjectBulk(array $identifiers, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3ObjectBulkDeleteTask($this->credentials, $onSuccess, $onError))->setObjectIdentifiers($identifiers));
    }

    /**
     * Check if a file exists in the AWS S3 bucket.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param callable $onSuccess a callback function which identifies if the object exists: <code>function(bool $exists) : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function isObjectExists(S3ObjectIdentifier $identifier, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3ObjectExistsTask($this->credentials, $onSuccess, $onError))->setObjectIdentifier($identifier));
    }

    /**
     * An AWS S3 bucket operation that checks for an object versions. This function will return all versions of an object
     * if versioning is enabled.
     *
     * @param S3ObjectIdentifier $identifier an AWS S3 bucket object identifier.
     * @param callable $onSuccess a callback function which returns all the object versions: <code>function(S3ObjectVersion[] $contents) : void{}</code>
     * @param callable|null $onError an optional callback when the operation has failed: <code>function(S3StorageError|Throwable $results) : void{}</code>
     */
    public function getObjectVersions(S3ObjectIdentifier $identifier, callable $onSuccess, ?callable $onError = null): void
    {
        $this->threadPool->submitTask((new S3ObjectVersionsTask($this->credentials, $onSuccess, $onError))->setObjectIdentifier($identifier));
    }
}