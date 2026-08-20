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
use function extension_loaded;
use function inflate_add;
use function inflate_init;
use function is_string;
use function libdeflate_deflate_compress;
use function zlib_encode;

/**
 * Raw DEFLATE (ZLIB_ENCODING_RAW) compression format.
 *
 * Compression uses ext-libdeflate when available, falling back to core zlib.
 * Decompression always uses core zlib raw inflate, as ext-libdeflate only
 * exposes compression bindings.
 */
class Deflate implements Compressor
{

    public const LEVEL_MIN = 0;
    public const LEVEL_MAX = 12;
    public const LEVEL_DEFAULT = 6;

    /**
     * @param string $data
     * @param int|null $level
     * @return string
     */
    public function compress(string $data, ?int $level = null): string
    {
        $level ??= self::LEVEL_DEFAULT;

        if ($level < self::LEVEL_MIN || $level > self::LEVEL_MAX) {
            throw new InvalidArgumentException(
                'Compression level must be between ' . self::LEVEL_MIN . ' and ' . self::LEVEL_MAX . ', ' . $level . ' given'
            );
        }

        if (extension_loaded('libdeflate')) {
            return libdeflate_deflate_compress($data, $level);
        }

        $result = zlib_encode($data, ZLIB_ENCODING_RAW, $level > 9 ? 9 : $level);
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
        $context = inflate_init(ZLIB_ENCODING_RAW);
        if ($context === false) {
            throw new RuntimeException('Failed to initialize inflate context');
        }

        $result = inflate_add($context, $data, ZLIB_FINISH);
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
        return CompressionFormat::DEFLATE;
    }
}
