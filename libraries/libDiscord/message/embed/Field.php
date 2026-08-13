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

use function substr;

class Field implements IEmbed
{
    public const NAME_MAX_LENGTH = 256;
    public const VALUE_MAX_LENGTH = 1024;

    public function __construct(public string $name, public string $value, public bool $inline)
    {
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            $this->name = substr($name, 0, self::NAME_MAX_LENGTH - 3) . '...';
        }
        if (mb_strlen($value) > self::VALUE_MAX_LENGTH) {
            $this->value = substr($value, 0, self::VALUE_MAX_LENGTH - 3) . '...';
        }
    }

    public static function create(string $name, string $value, bool $inline): self
    {
        return new self($name, $value, $inline);
    }

    public static function simple(string $name, string $value): self
    {
        return new self($name, $value, false);
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "value" => $this->value,
            "inline" => $this->inline
        ];
    }

}