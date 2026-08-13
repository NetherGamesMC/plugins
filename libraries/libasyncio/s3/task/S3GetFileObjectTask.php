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
use pocketmine\utils\Utils;

/**
 * Retrieves an S3 object as a file object.
 */
class S3GetFileObjectTask extends S3GetObjectTask
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
        parent::executeTask();

        if ($this->getCache() === null) {
            $this->setResult(false);
        } else {
            /** @phpstan-ignore-next-line */
            self::writeObject($this->objectPath, $this->getCache());

            $this->setCache(null);
            $this->setResult(true);
        }
    }

    public static function getFileObject(S3StorageCredentials $credentials, S3ObjectIdentifier $objectURI, string $objectPath): bool
    {
        $result = self::getObject($credentials, $objectURI);
        if ($result === null) {
            return false;
        }
        self::writeObject($objectPath, $result);
        return true;
    }

    private static function writeObject(string $objectPath, string $contents): void
    {
        $fileObject = Utils::assumeNotFalse(fopen($objectPath, "w+"), "Writing a file should never fail");

        try {
            @fwrite($fileObject, $contents);
        } finally {
            fclose($fileObject);
        }
    }
}