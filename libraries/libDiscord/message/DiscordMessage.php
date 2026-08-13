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

namespace libDiscord\message;

use InvalidArgumentException;
use JsonSerializable;
use libDiscord\message\embed\MessageEmbed;
use libDiscord\message\mentions\AllowedMentions;

class DiscordMessage implements JsonSerializable
{
    public const MAX_EMBEDS = 10;
    /**
     * When a username is supplied to this field,
     * this field is required.
     */
    public string $username = "NetherGamesMC";
    /**
     * When a URL is supplied to this field,
     * it will replace the webhook's avatar for that message.
     */
    public string $avatarUrl = "";

    public AllowedMentions $allowedMentions;

    /** @var MessageEmbed[] */
    protected array $embeds = [];

    /**
     * @param string $content
     * @param MessageEmbed[] $embeds
     * @param bool $textToSpeech
     * @param AllowedMentions|null $allowedMentions
     */
    public function __construct(
        public string    $content,
        array            $embeds = [],
        protected bool   $textToSpeech = false,
        ?AllowedMentions $allowedMentions = null
    )
    {
        foreach ($embeds as $embed) {
            $this->addEmbed($embed);
        }
        $this->allowedMentions = $allowedMentions ?? AllowedMentions::none();
    }

    public function addEmbed(MessageEmbed $embed): self
    {
        if (count($this->embeds) >= self::MAX_EMBEDS) {
            throw new InvalidArgumentException("Cannot add more than" . self::MAX_EMBEDS . " embeds in a message");
        }
        $this->embeds[] = $embed;
        return $this;
    }

    /**
     * @param MessageEmbed[] $embeds
     */
    public static function create(string $content, array $embeds, bool $textToSpeech, ?AllowedMentions $allowedMentions): self
    {
        return new self($content, $embeds, $textToSpeech, $allowedMentions);
    }

    public static function simple(string $content): self
    {
        return new self($content, [], false, null);
    }

    public static function embed(MessageEmbed ...$embeds): self
    {
        return new self("", $embeds, false, null);
    }

    public function addEmbeds(MessageEmbed ...$embeds): self
    {
        foreach ($embeds as $embed) {
            $this->addEmbed($embed);
        }
        return $this;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setAvatarUrl(string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function setAllowedMentions(AllowedMentions $allowedMentions): void
    {
        $this->allowedMentions = $allowedMentions;
    }

    public function jsonSerialize(): array
    {
        return [
            "content" => $this->content,
            "allowed_mentions" => $this->allowedMentions,
            "username" => $this->username,
            "avatar_url" => $this->avatarUrl,
            "embeds" => $this->embeds,
            "tts" => $this->textToSpeech
        ];
    }
}