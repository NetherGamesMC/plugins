<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class NGChatEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    /** @var string */
    private string $prefix = '';
    /** @var string */
    private string $staffPrefix = '§aChat Relay §r§l»§r ';
    /** @var string */
    private string $splitter = ' §r§l»§r ';

    public function __construct(
        Player         $player,
        private string $displayName,
        private string $message,
        /** @var Player[] */
        private array  $recipients
    )
    {
        $this->player = $player;
    }

    public function getStaffPrefix(): string
    {
        return $this->staffPrefix;
    }

    public function setStaffPrefix(string $staffPrefix): void
    {
        $this->staffPrefix = $staffPrefix;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getSplitter(): string
    {
        return $this->splitter;
    }

    public function setSplitter(string $splitter): void
    {
        $this->splitter = $splitter;
    }

    /**
     * @return Player[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }
}