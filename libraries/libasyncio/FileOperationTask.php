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

use InvalidArgumentException;
use pocketmine\scheduler\AsyncTask;
use function is_callable;

abstract class FileOperationTask extends AsyncTask
{
    /** @var string */
    protected string $source;
    /** @var bool */
    private bool $success = false;

    /** @var float */
    private float $taskTime = 0.0;

    public function __construct(string $source, ?callable $onSuccess = null)
    {
        $this->source = $source;
        $this->storeLocal('closure', $onSuccess);
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $this->taskTime = microtime(true);
    }

    public function onCompletion(): void
    {
        $this->taskTime = microtime(true) - $this->taskTime;
        $this->checkSuccess();

        if ($this->getSuccess()) {
            try {
                $onSuccess = $this->fetchLocal('closure');
            } catch (InvalidArgumentException) {
                $onSuccess = null;
            }

            if (is_callable($onSuccess)) {
                $onSuccess($this->taskTime);
            }
        }
    }

    abstract protected function checkSuccess(): void;

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }
}