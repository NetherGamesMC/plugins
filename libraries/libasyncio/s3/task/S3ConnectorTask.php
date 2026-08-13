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

namespace libasyncio\s3\task;

use InvalidArgumentException;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use pocketmine\scheduler\AsyncTask;
use RuntimeException;
use Throwable;

abstract class S3ConnectorTask extends AsyncTask
{
    // thread-local content, avoid large buffers being serialized into pthreads Threaded object :/
    private static mixed $cacheContent = null;

    private const TIMEOUT_RETRIES = 3; // Requeue failed queries.

    public const MODE_GET = 'GET';
    public const MODE_POST = 'POST';
    public const MODE_DELETE = 'DELETE';
    public const MODE_PUT = 'PUT';
    public const MODE_HEAD = 'HEAD';

    private const CREDENTIALS = 'credentials';
    private const DEFAULT_CLOSURE = 'closure';
    private const CLOSURE_ERROR = 'closure-error';

    /** @var int */
    private int $retries = 0;
    /** @var string|null */
    private ?string $credentials;

    public function __construct(S3StorageCredentials $credentials, callable $onSuccess, ?callable $onError = null)
    {
        $this->storeLocal(self::DEFAULT_CLOSURE, $onSuccess);
        $this->storeLocal(self::CLOSURE_ERROR, $onError);

        $this->credentials = igbinary_serialize($credentials);
    }

    /**
     * Execute the related task for an S3 object.
     */
    abstract public function executeTask(): void;

    public function onRun(): void
    {
        /** @var S3StorageCredentials $credentials */
        $credentials = igbinary_unserialize($this->credentials ?? throw new RuntimeException("Credentials object is not found."));
        $this->storeLocal(self::CREDENTIALS, $credentials);

        try {
            $this->executeTask();

            if ($this->getCache() !== null) {
                $this->setResult($this->getCache());
            }
        } catch (Throwable $error) {
            if ($error instanceof S3StorageError && $error->getCode() === S3StorageError::NETWORK_ERROR && $this->retries < self::TIMEOUT_RETRIES) {
                $this->retries++;

                $this->onRun();
            } else {
                $this->setResult($error);
            }
        } finally {
            $this->setCache(null);
        }
    }

    protected function setCache(mixed $content): void
    {
        self::$cacheContent = $content;
    }

    protected function getCache(): mixed
    {
        return self::$cacheContent;
    }

    public function onCompletion(): void
    {
        try {
            /** @var callable|null $success */
            $success = $this->fetchLocal(self::DEFAULT_CLOSURE);
            /** @var callable|null $onError */
            $onError = $this->fetchLocal(self::CLOSURE_ERROR);
        } catch (InvalidArgumentException) {
            return;
        }

        $result = $this->getResult();
        if ($result instanceof Throwable) {
            if ($onError !== null) {
                $onError($result);
            }
        } else if ($success !== null) {
            if ($result === null) {
                $success();
            } else {
                $success($result);
            }
        }
    }

    /**
     * @return S3StorageCredentials
     */
    public function getCredentials(): S3StorageCredentials
    {
        /** @var S3StorageCredentials $credentials */
        $credentials = $this->fetchLocal(self::CREDENTIALS);
        return $credentials;
    }

    /**
     * @param array<string, bool|float|int|resource|string|null> $parameters
     */
    protected static function getParameters(array $parameters): string
    {
        $parameters = array_map('strval', $parameters);
        S3StorageCredentials::sortMetaHeaders($parameters);
        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    protected static function getEtagFromHeaders(array $headersArray): string
    {
        $eTag = '';
        foreach ($headersArray as $headers) {
            if (empty($headers)) {
                continue;
            }

            if (!empty($eTag) && isset($headers['etag'])) {
                throw new S3StorageError("MD5 validation cannot occur, multiple etag found.", S3StorageError::MISMATCH_MD5);
            } else if (isset($headers['etag'])) {
                $eTag = $headers['etag'];
            }
        }

        if (empty($eTag)) {
            throw new S3StorageError("Etag could not be found, MD5 validation cannot occur.", S3StorageError::MISMATCH_MD5);
        }

        return substr($eTag, 1, -1);
    }

    protected static function hasAMZRequestId(array $headersArray): bool
    {
        foreach ($headersArray as $headers) {
            if (empty($headers)) {
                continue;
            }

            if (isset($headers['x-amz-request-id'])) {
                return true;
            }
        }

        return false;
    }
}