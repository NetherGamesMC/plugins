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
 * @author driesboy
 *
 */
declare(strict_types=1);

namespace libasyncio\compression;

use function str_ends_with;

enum CompressionFormat
{

    case ZSTD;
    case DEFLATE;
    case GZIP;

    /**
     * File extension used for compressed archives in this format (e.g. 'ngzstd').
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return match ($this) {
            self::ZSTD => 'ngzstd',
            self::DEFLATE => 'ngdeflate',
            self::GZIP => 'nggzip',
        };
    }

    /**
     * Name of the PHP extension required, or null when self-contained via core zlib.
     *
     * @return string|null
     */
    public function getRequiredPHPExtension(): ?string
    {
        return match ($this) {
            self::ZSTD => 'zstd',
            self::DEFLATE => null,
            self::GZIP => null,
        };
    }

    /**
     * @param string $path
     * @return self|null
     */
    public static function fromPath(string $path): ?self
    {
        foreach (self::cases() as $format) {
            if (str_ends_with($path, '.' . $format->getFileExtension())) {
                return $format;
            }
        }

        return null;
    }
}
