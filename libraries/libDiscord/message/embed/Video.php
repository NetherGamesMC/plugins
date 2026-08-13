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

class Video implements IEmbed
{

    public function __construct(
        public string $url,
        public int    $height,
        public int    $width,
        public string $proxyUrl
    )
    {
    }

    public static function create(string $url, int $height, int $width, string $proxyUrl): self
    {
        return new self($url, $height, $width, $proxyUrl);
    }

    public static function simple(string $url, int $height, int $width): self
    {
        return new self($url, $height, $width, "");
    }


    public function jsonSerialize(): array
    {
        return [
            "url" => $this->url,
            "height" => $this->height,
            "width" => $this->width,
            "proxy_url" => $this->proxyUrl
        ];
    }
}