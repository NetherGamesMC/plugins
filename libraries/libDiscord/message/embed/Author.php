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

use function mb_strlen;
use function substr;

class Author implements IEmbed
{
    public const NAME_MAX_LENGTH = 256;

    public function __construct(public string $name, public string $url, public string $iconUrl, public string $proxyIconUrl)
    {
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            $this->name = substr($name, 0, self::NAME_MAX_LENGTH - 3) . '...';
        }
    }

    public static function create(string $name, string $url, string $iconUrl, string $proxyIconUrl): self
    {
        return new self($name, $url, $iconUrl, $proxyIconUrl);
    }

    public static function simple(string $name, string $url): self
    {
        return new self($name, $url, "", "");
    }


    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "url" => $this->url,
            "icon_url" => $this->iconUrl,
            "proxy_icon_url" => $this->proxyIconUrl
        ];
    }

}