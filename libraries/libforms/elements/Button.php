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
use pocketmine\utils\TextFormat;
use function count;
use function str_repeat;
use function str_starts_with;
use function strlen;

class Button
{
    public function __construct(private string $text, private ?Closure $callable = null){}

    public function runCallable(Player $player): void
    {
        $callable = $this->getCallable();

        if ($callable !== null) {
            $callable($player);
        }
    }

    public function getCallable(): ?Closure
    {
        return $this->callable;
    }

    public function setCallable(?Closure $callable): void
    {
        $this->callable = $callable;
    }

    public function getData(Player $player, string $formType = ''): array
    {
        if ($formType !== '') {
            $lines = explode(TextFormat::EOL, $this->getText());
            $lineCount = count($lines);
            $lastKey = $lineCount - 1;
            $text = '';

            foreach ($lines as $index => $line) {
                if ($index === $lastKey) {
                    $text .= $line;
                } else {
                    $text .= $line . str_repeat(' ', 40 - (strlen($line) % 40));
                }
            }

            return ['text' => $text];
        }

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