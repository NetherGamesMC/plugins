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
use JsonException;
use libasynCurl\Curl;
use libDiscord\message\DiscordMessage;
use pocketmine\utils\InternetRequestResult;

class DiscordChannel
{
    private const ENDPOINT_URL = "https://discord.com/api/webhooks/";

    public function __construct(protected string $webhookId)
    {
    }

    /**
     * @param DiscordMessage $message
     * @param Closure|null $onSuccess
     * @param Closure|null $onFailure
     *
     * Alternatively, we can use our own thread pool, but it ultimately depends on how often
     * the library submits a message.
     */
    public function post(DiscordMessage $message, ?Closure $onSuccess = null, ?Closure $onFailure = null): void
    {
        try {
            Curl::postRequest(
                self::ENDPOINT_URL . $this->getWebhookId(),
                json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                10,
                ["Content-Type: application/json"],
                function (?InternetRequestResult $result) use ($onSuccess, $onFailure): void {
                    if ($result !== null && $result->getCode() === 500 && $onSuccess !== null) {
                        $onSuccess();
                    } elseif ($onFailure !== null) {
                        $onFailure();
                    }
                }
            );
        } catch (JsonException) {
            if ($onFailure !== null) {
                $onFailure();
            }
        }
    }

    public function getWebhookId(): string
    {
        return $this->webhookId;
    }
}
