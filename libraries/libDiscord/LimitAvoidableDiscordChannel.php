<?php
/**
 *  _ _ _     _____  _                       _
 * | (_) |   |  __ \(_)                     | |
 * | |_| |__ | |  | |_ ___  ___ ___  _ __ __| |
 * | | | '_ \| |  | | / __|/ __/ _ \| '__/ _` |
 * | | | |_) | |__| | \__ \ (_| (_) | | | (_| |
 * |_|_|_.__/|_____/|_|___/\___\___/|_|  \__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libDiscord;

use Closure;
use libDiscord\message\DiscordMessage;

/**
 * This channel is meant as a way to avoid Discord's rate-limiting system.
 *
 * Using this class, you can supply multiple webhooks and post to a randomized one.
 */
class LimitAvoidableDiscordChannel extends DiscordChannel
{
    /**
     * @param string[] $webhookIds
     */
    public function __construct(protected array $webhookIds)
    {
        parent::__construct(count($webhookIds) > 0 ? $webhookIds[array_rand($webhookIds)] : "");
    }

    public function post(DiscordMessage $message, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        if (count($this->webhookIds) === 0) {
            return;
        }
        parent::post($message, $onSuccess, $onFailure);
    }

    public function getWebhookId(): string
    {
        return count($this->webhookIds) > 0 ? $this->webhookIds[array_rand($this->webhookIds)] : "";
    }

}