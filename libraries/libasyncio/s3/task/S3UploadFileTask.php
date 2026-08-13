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

class S3UploadFileTask extends S3UploadObjectTask
{
    /** @var string */
    private string $objectPath;

    public function setObjectIdentifier(S3ObjectIdentifier $identifier): self
    {
        parent::setObjectIdentifier($identifier);
        return $this;
    }

    public function setFileLocation(string $objectPath): self
    {
        $this->objectPath = $objectPath;
        return $this;
    }

    public function executeTask(): void
    {
        $this->setObjectContents(self::readContents($this->objectPath));

        parent::executeTask();
    }

    public static function uploadFileObject(S3StorageCredentials $credentials, S3ObjectIdentifier $identifier, string $filePath): void
    {
        self::uploadObject($credentials, $identifier, self::readContents($filePath));
    }

    private static function readContents(string $filePath): string
    {
        if (!file_exists($filePath) || !is_file($filePath) || !is_readable($filePath)) {
            throw new S3StorageError("A file object must be a valid and readable file.", S3StorageError::INTERNAL_ERROR);
        }

        if (!is_string($contents = file_get_contents($filePath))) {
            throw new S3StorageError("The object content must not be empty, use delete to remove the object.", S3StorageError::INTERNAL_ERROR);
        }

        return $contents;
    }
}