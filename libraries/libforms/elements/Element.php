<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms\elements;

use Closure;
use pocketmine\player\Player;

abstract class Element
{
    public function __construct(private string $text, private ?Closure $callable = null, private readonly bool $callbackOnDefault = false){}

    /**
     * @param Player $player
     * @param mixed $data
     */
    public function runCallable(Player $player, $data): bool
    {
        $callable = $this->getCallable();

        if ($callable === null) {
            return false;
        }

        $callable($player, $data);
        return true;
    }

    public function getCallable(): ?Closure
    {
        return $this->callable;
    }

    public function setCallable(?Closure $callable): void
    {
        $this->callable = $callable;
    }

    /**
     * @return bool
     */
    public function isCallbackOnDefault(): bool
    {
        return $this->callbackOnDefault;
    }

    public function getData(Player $player): array
    {
        return ['text' => $this->getText()];
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}