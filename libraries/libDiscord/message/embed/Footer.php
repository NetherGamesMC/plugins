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

namespace libDiscord\message\embed;

class Footer implements IEmbed
{

    public function __construct(
        public string $text,
        public string $iconUrl,
        public string $proxyIconUrl
    )
    {
    }

    public static function create(string $text, string $iconUrl, string $proxyIconUrl): self
    {
        return new self($text, $iconUrl, $proxyIconUrl);
    }

    public static function simple(string $text, string $iconUrl): self
    {
        return new self($text, $iconUrl, "");
    }

    public function jsonSerialize(): array
    {
        return [
            "text" => $this->text,
            "icon_url" => $this->iconUrl,
            "proxy_icon_url" => $this->proxyIconUrl
        ];
    }
}