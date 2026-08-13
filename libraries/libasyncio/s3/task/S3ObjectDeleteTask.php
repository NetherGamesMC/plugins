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

use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use RuntimeException;

/**
 * Delete *an* object in the bucket, provide a version id if you want to delete the previous versions of an object.
 * Please use bulking-delete if you want to delete more than one object, this helps to reduce per-request overhead.
 */
class S3ObjectDeleteTask extends S3ConnectorTask
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

    public function executeTask(): void
    {
        /** @var S3ObjectIdentifier $objectIdentifier */
        $objectIdentifier = igbinary_unserialize($this->objectIdentifier);

        $this->setResult(self::deleteObject($this->getCredentials(), $objectIdentifier));
    }

    public static function deleteObject(S3StorageCredentials $credentials, S3ObjectIdentifier $objectIdentifier): bool
    {
        $object = $objectIdentifier->getObjectURI();
        $objectVersion = $objectIdentifier->getObjectVersion();

        $endpoint = $credentials->getEndpoint();

        // Check for object version id.
        if (!empty($objectVersion)) {
            $headers = $credentials->getS3OperationHeader($endpoint, self::MODE_DELETE, '/' . $object, [], [], $queryString = self::getParameters([
                'versionId' => $objectVersion,
            ]));

            $page = "https://$endpoint/{$credentials->getBucket()}/$object?$queryString";
        } else {
            $headers = $credentials->getS3OperationHeader($endpoint, self::MODE_DELETE, '/' . $object);

            $page = "https://$endpoint/{$credentials->getBucket()}/$object";
        }

        try {
            $result = Internet::simpleCurl($page, 10, $headers, [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_POSTFIELDS => ""
            ]);
        } catch (InternetException $exception) {
            throw new S3StorageError("Connection failed, " . $exception->getMessage(), S3StorageError::NETWORK_ERROR);
        }

        if ($result->getCode() === 204 || ($result->getCode() === 404 && self::hasAMZRequestId($result->getHeaders()))) {
            return $result->getCode() === 204;
        }

        throw new S3StorageError("Storage error [Code: {$result->getCode()}], Debug Info: " . (empty($result->getBody()) ? ' -- EMPTY --' : $result->getBody()) . ', Headers: ' . json_encode($result->getHeaders()), S3StorageError::STORAGE_ERROR);
    }
}