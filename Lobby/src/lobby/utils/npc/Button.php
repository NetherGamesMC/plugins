<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace lobby\utils\npc;

use Closure;
use JsonSerializable;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class Button implements JsonSerializable
{
    private const MODE_BUTTON = 0;
    private const MODE_ON_CLOSE = 1;

    private const TYPE_URL = 0; // ???
    private const TYPE_COMMAND = 1;
    private const TYPE_INVALID = 2;

    /** @var string */
    private string $name;
    /** @var string */
    private string $text = ''; // ???
    /** @var array|null */
    private ?array $data = null; // ???
    /** @var int */
    private int $mode = self::MODE_BUTTON; // ???
    /** @var int */
    private int $type = self::TYPE_COMMAND; // ????
    /** @var Closure|null */
    private ?Closure $submitListener;

    public function __construct(string $name, ?Closure $submitListener = null, private ?string $args = null)
    {
        $this->name = $name;
        $this->setSubmitListener($submitListener);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getArgs(): ?string
    {
        return $this->args;
    }

    public function getSubmitListener(): ?Closure
    {
        return $this->submitListener;
    }

    public function setSubmitListener(?Closure $submitListener): void
    {
        if ($submitListener !== null) {
            Utils::validateCallableSignature(function (Player $player) {
            }, $submitListener);
        }

        $this->submitListener = $submitListener;
    }

    public function executeSubmitListener(Player $player): void
    {
        if ($this->submitListener !== null) {
            ($this->submitListener)($player);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            "button_name" => $this->name,
            "text" => $this->text ?? "",
            "data" => $this->data ?? [],
            "mode" => $this->mode,
            "type" => $this->type,
        ];
    }
}