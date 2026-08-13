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

namespace libasyncio\s3\response;

use libasyncio\s3\S3StorageError;

/**
 * Documentation: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ObjectIdentifier.html
 */
class S3ObjectIdentifier
{
    private string $objectURI;

    /**
     * @param string $objectKey The object file
     * @param string $objectVersion (Optional) The object version for a specified key.
     */
    public function __construct(
        private string $objectKey,
        private string $objectVersion = "")
    {
        if (strlen($this->objectKey) < 1) {
            throw new S3StorageError("Object key must have a minimum length of 1.");
        }

        $this->objectURI = str_replace('%2F', '/', rawurlencode($this->objectKey));
    }

    /**
     * @return string The object key
     */
    public function getObjectKey(): string
    {
        return $this->objectKey;
    }

    /**
     * @return string The object URI
     */
    public function getObjectURI(): string
    {
        return $this->objectURI;
    }

    /**
     * @return string
     */
    public function getObjectVersion(): string
    {
        return $this->objectVersion;
    }
}