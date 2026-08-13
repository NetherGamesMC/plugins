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
use pocketmine\utils\Filesystem;
use Throwable;

class FileDeleteAsyncTask extends FileOperationTask
{
    public function onRun(): void
    {
        parent::onRun();

        try {
            Filesystem::recursiveUnlink($this->source);
            $this->setSuccess(true);
        } catch (Throwable $e) {
            $this->setSuccess(false);
        }
    }

    protected function checkSuccess(): void
    {
        if ($this->getSuccess()) {
            Server::getInstance()->getLogger()->debug("Deleted file {$this->source}");
        } else {
            Server::getInstance()->getLogger()->error("Unable to delete file {$this->source}");
        }
    }

}