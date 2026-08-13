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

use pocketmine\Server;

class FileOrDirectoryCompressTask extends FileOperationTask
{

    /** @var string */
    private string $input;
    /** @var string */
    private $output;
    /** @var int */
    private $compressionLevel;

    /**
     * FileOrDirectoryCompressTask constructor.
     *
     * @param string $input
     * @param string $output
     * @param callable $callable
     * @param int $compressionLevel
     */
    public function __construct(string $input, string $output, callable $callable, int $compressionLevel = ZstdRecursiveCompressor::COMPRESSION_LEVEL)
    {
        $this->input = $input;
        $this->output = str_replace('.' . ZstdRecursiveCompressor::COMPRESSION_FORMAT, '', $output);
        $this->compressionLevel = $compressionLevel;
        parent::__construct($input, $callable);
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        parent::onRun();
        $this->setSuccess(ZstdRecursiveCompressor::compress($this->input, $this->output, $this->compressionLevel));
    }

    protected function checkSuccess(): void
    {
        $outputLocation = $this->output . '.' . ZstdRecursiveCompressor::COMPRESSION_FORMAT;
        if ($this->getSuccess()) {
            Server::getInstance()->getLogger()->debug("Compressed directory/file {$this->input} to {$outputLocation}");
        } else {
            Server::getInstance()->getLogger()->error("Unable to compress file {$this->input} to {$outputLocation}");
        }
    }

}