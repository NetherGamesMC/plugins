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
use libasyncio\s3\utils\Internet;
use RuntimeException;

class S3UploadObjectTask extends S3ConnectorTask
{
    /** @var string */
    private string $objectContents;
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

    public function setObjectContents(string $objectContents): self
    {
        // fail-safe
        if (empty($objectContents)) {
            throw new S3StorageError("The object content must not be empty, use delete to remove the object.", S3StorageError::INTERNAL_ERROR);
        }

        $this->objectContents = $objectContents;
        return $this;
    }

    public function executeTask(): void
    {
        /** @var S3ObjectIdentifier $objectIdentifier */
        $objectIdentifier = igbinary_unserialize($this->objectIdentifier);

        self::uploadObject($this->getCredentials(), $objectIdentifier, $this->objectContents);
    }

    public static function uploadObject(S3StorageCredentials $credentials, S3ObjectIdentifier $identifier, string $contents): void
    {
        $object = $identifier->getObjectURI();

        $md5sum = base64_encode($md5 = md5($contents, true));
        $sha256sum = hash('sha256', $contents);

        $endpoint = $credentials->getEndpoint();
        $headers = $credentials->getS3OperationHeader($endpoint, self::MODE_PUT, '/' . $object, [
            'x-amz-content-sha256' => $sha256sum,
            'x-amz-acl' => "private"
        ], [
            'Content-MD5' => $md5sum,
            'Content-Type' => 'application/octet-stream'
        ]);

        $result = Internet::putURL("https://$endpoint/{$credentials->getBucket()}/$object", $contents, 30, $headers, $err);
        if ($result === null) {
            throw new S3StorageError("Connection failed, " . $err, S3StorageError::NETWORK_ERROR);
        }

        if ($result->getCode() === 200) {
            $digest = self::getEtagFromHeaders($result->getHeaders());

            // Validate the etag, just in case.
            if ($digest !== bin2hex($md5)) {
                throw new S3StorageError("MD5 validation error, please retry to upload.", S3StorageError::MISMATCH_MD5);
            }
        } else {
            throw new S3StorageError("Storage error [Code: {$result->getCode()}], Debug Info: " . (empty($result->getBody()) ? ' -- EMPTY --' : $result->getBody()) . ', Headers: ' . json_encode($result->getHeaders()), S3StorageError::STORAGE_ERROR);
        }
    }
}