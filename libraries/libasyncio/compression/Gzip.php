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

use InvalidArgumentException;
use RuntimeException;
use function gzdecode;
use function gzencode;
use function is_string;

class Gzip implements Compressor
{

    public const LEVEL_MIN = 0;
    public const LEVEL_MAX = 9;

    /**
     * @param string $data
     * @param int|null $level
     * @return string
     */
    public function compress(string $data, ?int $level = null): string
    {
        if ($level !== null && ($level < self::LEVEL_MIN || $level > self::LEVEL_MAX)) {
            throw new InvalidArgumentException(
                'Compression level must be between ' . self::LEVEL_MIN . ' and ' . self::LEVEL_MAX . ', ' . $level . ' given'
            );
        }

        $result = gzencode($data, $level ?? -1);
        if (!is_string($result)) {
            throw new RuntimeException('Compression failed');
        }

        return $result;
    }

    /**
     * @param string $data
     * @return string
     */
    public function decompress(string $data): string
    {
        $result = gzdecode($data);
        if (!is_string($result)) {
            throw new RuntimeException('Uncompression failed');
        }

        return $result;
    }

    /**
     * @return CompressionFormat
     */
    public function getFormat(): CompressionFormat
    {
        return CompressionFormat::GZIP;
    }
}
