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
use libasyncio\compression\CompressionFormat;
use libasyncio\compression\Compressor;
use Phar;
use PharData;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Throwable;
use function is_dir;
use function is_file;
use function mkdir;
use function str_ends_with;

class RecursiveCompressor
{

    public const ARCHIVE_FORMAT = 'tar';

    /**
     * Compress a directory.
     * The output should be a directory
     * like path. It's important you don't
     * use a file name for it.
     *
     * Output format is the chosen compression format.
     *
     * @param string $input
     * @param string $output
     * @param int|null $compressionLevel
     * @param CompressionFormat|null $format
     *
     * @return bool
     */
    public static function compress(string $input, string $output, ?int $compressionLevel = null, ?CompressionFormat $format = null): bool
    {
        $compressor = self::resolveCompressor($format);

        $archive = new PharData($input . '.' . self::ARCHIVE_FORMAT);
        $archive->buildFromDirectory($input);

        $data = file_get_contents($archive->getPath());
        if ($data === false) {
            throw new RuntimeException('Archive unreadable');
        }

        $compressedData = $compressor->compress($data, $compressionLevel);

        Filesystem::safeFilePutContents($output . '.' . $compressor->getFormat()->getFileExtension(), $compressedData);

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
     * Input format is the chosen compression format.
     * Output format is regular directory.
     *
     * @param string $input
     * @param string $output
     * @param CompressionFormat|null $format
     *
     * @return bool
     */
    public static function uncompress(string $input, string $output, ?CompressionFormat $format = null): bool
    {
        $compressor = self::resolveCompressor($format, $input);

        $extension = $compressor->getFormat()->getFileExtension();
        if (!str_ends_with($input, '.' . $extension)) {
            $input .= '.' . $extension;
        }

        if (!is_file($input)) {
            throw new RuntimeException(
                'That file is not of type ' . $extension . ', cannot uncompress'
            );
        }

        $compressedData = file_get_contents($input);
        if ($compressedData === false) {
            throw new RuntimeException('Compressed file unreadable');
        }

        $data = $compressor->decompress($compressedData);

        Filesystem::safeFilePutContents($output . '.' . self::ARCHIVE_FORMAT, $data);
        $archive = new PharData($output . '.' . self::ARCHIVE_FORMAT);

        try {
            if (!is_dir($output) && !mkdir($output)) {
                throw new RuntimeException('Directory "' . $output . '" was not created');
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

    /**
     * @param CompressionFormat|null $format
     * @param string|null $path
     *
     * @return Compressor
     */
    private static function resolveCompressor(?CompressionFormat $format, ?string $path = null): Compressor
    {
        if ($format !== null) {
            return $format->getCompressor();
        }

        return ($path !== null ? (CompressionFormat::fromPath($path) ?? CompressionFormat::auto()) : CompressionFormat::auto())->getCompressor();
    }
}
