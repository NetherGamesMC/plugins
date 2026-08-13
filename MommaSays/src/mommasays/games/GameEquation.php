<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\games;

use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\utils\TextFormat;
use function is_float;
use function random_int;

class GameEquation extends Game
{
    /** @var int */
    public int $solution = 0;
    /** @var string */
    public string $formatEquation = '';

    public function setupArena(): void
    {
        $first = random_int(1, 10);
        $second = random_int(1, 10);

        switch (random_int(1, 4)) {
            case 1:
                $solution = $first + $second;
                $formatOperator = '+';
                break;
            case 2:
                $solution = $first - $second;
                $formatOperator = '-';
                break;
            case 3:
                $solution = $first / $second;
                $formatOperator = '/';
                break;
            default:
                $solution = $first * $second;
                $formatOperator = 'x';
                break;
        }

        if (is_float($solution)) { // this is a decimal number
            $this->setupArena();
        } else {
            $this->formatEquation = $first . ' ' . $formatOperator . ' ' . $second;
            $this->solution = $solution;
        }
    }

    public function onPlayerChat(NGChatEvent $event): void
    {
        $player = $event->getPlayer();

        if ($this->isWinner($player->getName())) {
            $event->cancel();
            $player->sendMessage(TextFormat::RED . "Isn't it boring if you tell everyone the solution?");
        } elseif (TextFormat::clean($event->getMessage()) === (string)$this->solution) {
            $this->addWinner($player);
            $event->cancel();
        }
    }

    public function getMessage(): string
    {
        return 'Solve the equation ' . TextFormat::RED . $this->formatEquation;
    }
}