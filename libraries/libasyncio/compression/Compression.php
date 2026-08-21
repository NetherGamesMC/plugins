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
use function extension_loaded;

final class Compression
{

    /**
     * @param CompressionFormat $format
     * @return Compressor
     */
    public static function get(CompressionFormat $format): Compressor
    {
        if (!$format->isCompatible()) {
            throw new InvalidArgumentException('Compression format ' . $format->name . ' is not compatible');
        }

        return match ($format) {
            CompressionFormat::ZSTD => new Zstd(),
            CompressionFormat::DEFLATE => new Deflate(),
            CompressionFormat::GZIP => new Gzip(),
        };
    }

    /**
     * @return Compressor
     */
    public static function auto(): Compressor
    {
        if (extension_loaded('zstd')) {
            return new Zstd();
        }

        if (extension_loaded('libdeflate')) {
            return new Deflate();
        }

        return new Gzip();
    }

    /**
     * @param string $path
     * @return Compressor
     */
    public static function fromPath(string $path): Compressor
    {
        $format = CompressionFormat::fromPath($path);

        return $format !== null ? self::get($format) : self::auto();
    }
}
