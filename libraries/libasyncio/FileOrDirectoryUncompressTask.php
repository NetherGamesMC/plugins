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

use libasyncio\compression\Compression;
use libasyncio\compression\CompressionFormat;
use libasyncio\compression\Compressor;
use pocketmine\Server;
use function str_ends_with;

class FileOrDirectoryUncompressTask extends FileOperationTask
{

    /** @var string */
    private string $input;
    /** @var string */
    private string $output;
    /** @var Compressor */
    private Compressor $compressor;

    /**
     * FileOrDirectoryUncompressTask constructor.
     *
     * @param string $input
     * @param string $output
     * @param callable $callable
     * @param CompressionFormat|null $format
     */
    public function __construct(string $input, string $output, callable $callable, ?CompressionFormat $format = null)
    {
        $this->compressor = $format !== null ? Compression::get($format) : Compression::fromPath($input);
        $this->input = $input;
        $this->output = $output;
        parent::__construct($input, $callable);
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        parent::onRun();
        $this->setSuccess(RecursiveCompressor::uncompress($this->input, $this->output, $this->compressor->getFormat()));
    }

    protected function checkSuccess(): void
    {
        $extension = $this->compressor->getFormat()->getFileExtension();
        $inputLocation = str_ends_with($this->input, '.' . $extension) ? $this->input : $this->input . '.' . $extension;

        if ($this->getSuccess()) {
            Server::getInstance()->getLogger()->debug("Uncompressed directory/file {$inputLocation} to {$this->output}");
        } else {
            Server::getInstance()->getLogger()->error("Unable to uncompress file {$inputLocation} to {$this->output}");
        }
    }

}
