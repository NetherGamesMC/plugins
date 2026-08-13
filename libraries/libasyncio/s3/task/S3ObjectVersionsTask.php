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

use DateTime;
use Exception;
use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\response\S3ObjectVersion;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use pocketmine\utils\Internet;
use RuntimeException;

/**
 * Retain all version of a particular object into a list of S3ObjectVersion.
 */
class S3ObjectVersionsTask extends S3ConnectorTask
{
    /** @var string */
    private string $objectIdentifier;

    public function setObjectIdentifier(S3ObjectIdentifier $identifier): self
    {
        $serialized = igbinary_serialize($identifier);
        if (!is_string($serialized)) {
            throw new RuntimeException("Serialized S3ObjectIdentifier must always be string");
        }

        $this->objectIdentifier = $serialized;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function executeTask(): void
    {
        /** @var S3ObjectIdentifier $objectIdentifier */
        $objectIdentifier = igbinary_unserialize($this->objectIdentifier);

        $this->setResult(self::getObjectVersions($this->getCredentials(), $objectIdentifier));
    }

    /**
     * @param S3StorageCredentials $credentials
     * @param S3ObjectIdentifier $identifier
     * @return S3ObjectVersion[]
     * @throws Exception
     */
    public static function getObjectVersions(S3StorageCredentials $credentials, S3ObjectIdentifier $identifier): array
    {
        $endpoint = $credentials->getEndpoint();
        $headers = $credentials->getS3OperationHeader($endpoint, self::MODE_GET, '/', [], [], $queryString = self::getParameters([
            'versions' => null,
            'prefix' => $identifier->getObjectKey()
        ]));

        $result = Internet::getURL("https://$endpoint/{$credentials->getBucket()}/?$queryString", 10, $headers, $err);
        if ($result === null) {
            throw new S3StorageError("Connection failed, " . $err, S3StorageError::NETWORK_ERROR);
        }

        if ($result->getCode() === 200) {
            $decode = simplexml_load_string($result->getBody(), null, LIBXML_NOCDATA);

            $versions = [];

            /** @phpstan-ignore-next-line */
            foreach ($decode->Version as $version) {
                $versions[] = new S3ObjectVersion(
                    (string)$version->Key,
                    (string)$version->VersionId,
                    (bool)$version->IsLatest,
                    substr((string)$version->ETag, 1, -1),
                    new DateTime((string)$version->LastModified)
                );
            }

            return $versions;
        } else {
            throw new S3StorageError("Storage error [Code: {$result->getCode()}], Debug Info: " . (empty($result->getBody()) ? ' -- EMPTY --' : $result->getBody()) . ', Headers: ' . json_encode($result->getHeaders()), S3StorageError::STORAGE_ERROR);
        }
    }
}