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

namespace libasyncio;

use GlobalLogger;
use Phar;
use PharData;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Throwable;
use function is_dir;
use function mkdir;
use function sprintf;

class ZstdRecursiveCompressor
{

    /** @var int */
    public const COMPRESSION_LEVEL = ZSTD_COMPRESS_LEVEL_MAX;

    public const ARCHIVE_FORMAT = 'tar';
    public const COMPRESSION_FORMAT = 'ngzstd';

    /**
     * Compress a directory.
     * The output should be a directory
     * like path. It's important you don't
     * use a file name for it.
     *
     * Output format is COMPRESSION_FORMAT.
     *
     * @param string $input
     * @param string $output
     * @param int $compressionLevel
     *
     * @return bool
     */
    public static function compress(string $input, string $output, int $compressionLevel = self::COMPRESSION_LEVEL): bool
    {
        if ($compressionLevel < ZSTD_COMPRESS_LEVEL_MIN || $compressionLevel > ZSTD_COMPRESS_LEVEL_MAX) {
            throw new RuntimeException(
                'Compression level must cannot either lower than ' . ZSTD_COMPRESS_LEVEL_MIN .
                ' or higher than ' . ZSTD_COMPRESS_LEVEL_MAX . ', ' . $compressionLevel . ' given'
            );
        }

        $archive = new PharData($input . '.' . self::ARCHIVE_FORMAT);
        $archive->buildFromDirectory($input);

        $data = file_get_contents($archive->getPath());
        if ($data === false) {
            throw new RuntimeException('Archive unreadable');
        }

        $compressedData = zstd_compress($data, $compressionLevel);
        if (!is_string($compressedData)) {
            throw new RuntimeException('Compression failed');
        }

        Filesystem::safeFilePutContents($output . '.' . self::COMPRESSION_FORMAT, $compressedData);

        unset($archive);
        Phar::unlinkArchive($input . '.' . self::ARCHIVE_FORMAT);
        return true;
    }

    /**
     * Decompress a directory.
     * The input should be a directory
     * like path. It's important you don't
     * use a file name for it.
     *
     * Input format is COMPRESSION_FORMAT.
     * Output format is regular directory.
     *
     * @param string $input
     * @param string $output
     *
     * @return bool
     */
    public static function uncompress(string $input, string $output): bool
    {
        $input .= '.' . self::COMPRESSION_FORMAT;
        if (!is_file($input)) {
            throw new RuntimeException(
                'That file is not of type ' . self::COMPRESSION_FORMAT . ', cannot uncompress'
            );
        }

        $compressedData = file_get_contents($input);
        if ($compressedData === false) {
            throw new RuntimeException('Compressed file unreadable');
        }

        $data = zstd_uncompress($compressedData);
        if (!is_string($data)) {
            throw new RuntimeException('Uncompression failed.');
        }

        Filesystem::safeFilePutContents($output . '.' . self::ARCHIVE_FORMAT, $data);
        $archive = new PharData($output . '.' . self::ARCHIVE_FORMAT);

        try {
            if (!is_dir($output) && !mkdir($output)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $output));
            }
        } catch (Throwable $exception) {
            GlobalLogger::get()->critical("Unhandled exception from a method that should never throw anything.");
            GlobalLogger::get()->logException($exception);
        }

        $archive->extractTo($output);

        unset($archive);

        PharData::unlinkArchive($output . '.' . self::ARCHIVE_FORMAT);

        return true;
    }
}