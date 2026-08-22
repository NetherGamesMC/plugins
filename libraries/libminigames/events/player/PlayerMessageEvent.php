<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\events\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/**
 * Fired for every message libminigames wants to send to a player.
 *
 * @internal This is a temporary transport until libminigames gets proper i18n support. See {@see \libminigames\utils\Messages}.
 * @todo Replace with a proper i18n system.
 */
class PlayerMessageEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    /**
     * @param string $key The hardcoded translation key.
     * @param array<string, string> $args The arguments used to replace placeholders in the message.
     * @param int $medium The {@see \libminigames\utils\TextType} medium the message should be sent through.
     * @param string $style The style identifier (e.g. 'success', 'error', 'info').
     * @param string|null $fallback The raw English message used when no i18n consumer is present.
     * @param string $message The current message. Defaults to the fallback when provided.
     */
    public function __construct(
        Player          $player,
        private string  $key,
        private array   $args,
        private int     $medium,
        private string  $style,
        private ?string $fallback,
        private string  $message
    )
    {
        parent::__construct($player);
    }

    /**
     * @param array<string, string> $args The arguments used to replace placeholders in the message.
     */
    public static function make(Player $player, string $key, array $args, int $medium, string $style, ?string $fallback = null): self
    {
        return new self(
            player: $player,
            key: $key,
            args: $args,
            medium: $medium,
            style: $style,
            fallback: $fallback,
            message: $fallback ?? ''
        );
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array<string, string>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    public function getMedium(): int
    {
        return $this->medium;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getFallback(): ?string
    {
        return $this->fallback;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}