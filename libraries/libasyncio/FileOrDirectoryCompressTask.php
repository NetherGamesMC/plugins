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
use InvalidArgumentException;
use libasyncio\compression\CompressionFormat;
use libasyncio\compression\Compressor;
use pocketmine\Server;
use RuntimeException;

class FileOrDirectoryCompressTask extends FileOperationTask
{

    /** @var string */
    private string $input;
    /** @var string */
    private string $output;
    /** @var int|null */
    private ?int $compressionLevel;
    /** @var Compressor */
    private Compressor $compressor;

    /**
     * FileOrDirectoryCompressTask constructor.
     *
     * @param string $input
     * @param string $output
     * @param callable $callable
     * @param int|null $compressionLevel
     * @param CompressionFormat|null $format
     */
    public function __construct(string $input, string $output, callable $callable, ?int $compressionLevel = null, ?CompressionFormat $format = null)
    {
        if ($format !== null && !$format->isCompatible()) {
            throw new InvalidArgumentException('Compression format ' . $format->name . ' is not compatible');
        }

        $this->compressor = ($format ?? CompressionFormat::auto())->getCompressor();
        $this->input = $input;
        $this->output = ($outputFormat = CompressionFormat::fromPath($output)) !== null ? substr($output, 0, -strlen('.' . $outputFormat->getFileExtension())) : $output;
        $this->compressionLevel = $compressionLevel;
        parent::__construct($input, $callable);
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        parent::onRun();
        try {
            $this->setSuccess(RecursiveCompressor::compress($this->input, $this->output, $this->compressionLevel, $this->compressor->getFormat()));
        } catch (RuntimeException $e) {
            GlobalLogger::get()->critical("Compression failed for {$this->input}: " . $e->getMessage());
            GlobalLogger::get()->logException($e);
            $this->setSuccess(false);
        }
    }

    protected function checkSuccess(): void
    {
        $outputLocation = $this->output . '.' . $this->compressor->getFormat()->getFileExtension();
        if ($this->getSuccess()) {
            Server::getInstance()->getLogger()->debug("Compressed directory/file {$this->input} to {$outputLocation}");
        } else {
            Server::getInstance()->getLogger()->error("Unable to compress file {$this->input} to {$outputLocation}");
        }
    }

}
