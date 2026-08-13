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

use InvalidArgumentException;
use JsonSerializable;
use libDiscord\message\DiscordMessage;
use pocketmine\color\Color;
use function mb_strlen;
use function substr;

class MessageEmbed implements JsonSerializable
{

    public const MAX_FIELDS = 25;
    public const TITLE_MAX_LENGTH = 256;
    public const DESCRIPTION_MAX_LENGTH = 4096;

    public ?Footer $footer = null;
    public ?Image $image = null;
    public ?Thumbnail $thumbnail = null;
    public ?Video $video = null;
    public ?Provider $provider = null;
    public ?Author $author = null;

    public function __construct(
        public string    $title,
        public string    $type,
        public int|float $color,
        public string    $description,
        public string    $url,
        public array     $fields
    )
    {
        if (mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            $this->title = substr($title, 0, self::TITLE_MAX_LENGTH - 3) . '...';
        }
        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            $this->description = substr($description, 0, self::DESCRIPTION_MAX_LENGTH - 3) . '...';
        }
    }

    public static function create(string $title, string $type, int|float $color, string $description, string $url, array $fields): self
    {
        return new self($title, $type, $color, $description, $url, $fields);
    }

    public static function simple(string $title, string $type): self
    {
        return new self($title, $type, hexdec("FFFFFF"), "", "", []);
    }

    public static function rich(string $title): self
    {
        return new self($title, EmbedType::RICH, hexdec("FFFFFF"), "", "", []);
    }

    public function setDescription(string $description): self
    {
        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            $description = substr($description, 0, self::DESCRIPTION_MAX_LENGTH - 3) . '...';
        }
        $this->description = $description;
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function addFields(Field ...$fields): self
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }

    public function addField(Field $field): self
    {
        if (count($this->fields) >= self::MAX_FIELDS) {
            throw new InvalidArgumentException("Cannot add more than" . self::MAX_FIELDS . " fields");
        }
        $this->fields[] = $field;
        return $this;
    }

    /**
     * @param Field[] $fields
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function setColor(string|Color $color): self
    {
        if ($color instanceof Color) {
            $color = sprintf("%02x%02x%02x", $color->getR(), $color->getG(), $color->getB());
        }
        $this->color = hexdec($color);
        return $this;
    }

    public function setFooter(?Footer $footer): self
    {
        $this->footer = $footer;
        return $this;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;
        return $this;

    }

    public function setThumbnail(?Thumbnail $thumbnail): self
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    public function setVideo(?Video $video): self
    {
        $this->video = $video;
        return $this;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Wraps the embed in a Discord message
     *
     * @return DiscordMessage
     */
    public function asMessage(): DiscordMessage
    {
        return DiscordMessage::embed($this);
    }

    public function jsonSerialize(): array
    {
        $data = [
            "title" => $this->title,
            "type" => $this->type,
            "color" => $this->color,
            "description" => $this->description,
            "url" => $this->url,
            "timestamp" => date(DATE_ISO8601, time()),
            "fields" => $this->fields
        ];
        if ($this->footer !== null) $data["footer"] = $this->footer;
        if ($this->image !== null) $data["image"] = $this->image;
        if ($this->thumbnail !== null) $data["thumbnail"] = $this->thumbnail;
        if ($this->video !== null) $data["video"] = $this->video;
        if ($this->provider !== null) $data["provider"] = $this->provider;
        if ($this->author !== null) $data["author"] = $this->author;
        return $data;
    }
}