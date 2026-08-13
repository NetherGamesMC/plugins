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

namespace libforms\elements\translations;

use Closure;
use libforms\elements\Button;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\player\Player;

class TranslatedButton extends Button
{
    /**
     * @param string[] $parameters
     */
    public function __construct(string $text, private array $parameters = [], ?Closure $callable = null)
    {
        parent::__construct($text, $callable);
    }

    public function getData(Player $player, string $formType = ''): array
    {
        if ($player instanceof NGPlayer) {
            return ['text' => Translator::getTranslationPlayer($player, $this->getText(), Translator::TYPE_DEFAULT, ...$this->getParameters())];
        }

        return parent::getData($player, $formType);
    }

    /**
     * @return string[]
     */
    private function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string[] $parameters
     */
    public function setText(string $text, array $parameters = []): void
    {
        $this->parameters = $parameters;

        parent::setText($text);
    }
}