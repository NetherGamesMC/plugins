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

use DateTime;

/**
 * Documentation: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ObjectVersion.html
 */
class S3ObjectVersion
{
    public function __construct(
        private string   $objectName,
        private ?string  $versionId,
        private bool     $isLatest,
        private string   $eTag,
        private DateTime $lastModified)
    {
    }

    /**
     * @return string
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * @return string|null
     */
    public function getVersionId(): ?string
    {
        return $this->versionId;
    }

    /**
     * @return bool
     */
    public function isLatest(): bool
    {
        return $this->isLatest;
    }

    /**
     * @return string
     */
    public function getETag(): string
    {
        return $this->eTag;
    }

    /**
     * @return DateTime
     */
    public function getLastModified(): DateTime
    {
        return $this->lastModified;
    }
}