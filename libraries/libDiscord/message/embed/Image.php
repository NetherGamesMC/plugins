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

class Image implements IEmbed
{

    public function __construct(
        public string $url,
        public int    $width,
        public int    $height,
        public string $proxyUrl
    )
    {
    }

    public static function create(string $url, int $width, int $height, string $proxyUrl): self
    {
        return new self($url, $width, $height, $proxyUrl);
    }

    public static function simple(string $url, int $width, int $height): self
    {
        return new self($url, $width, $height, "");
    }

    public function jsonSerialize(): array
    {
        return [
            "url" => $this->url,
            "width" => $this->width,
            "height" => $this->height,
            "proxy_url" => $this->proxyUrl
        ];
    }
}