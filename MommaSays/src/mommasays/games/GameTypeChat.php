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
use function array_rand;
use function strtolower;
use function ucfirst;

class GameTypeChat extends Game
{
    /** @var string */
    private string $word;

    public function onPlayerChat(NGChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        if (strtolower(TextFormat::clean($message)) === $this->getWord()) {
            if (!$this->isWinner($player->getName())) {
                $this->addWinner($player);
            }

            $event->cancel();
        }
    }

    public function getMessage(): string
    {
        return 'Type the word ' . TextFormat::RED . ucfirst($this->getWord());
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function setupArena(): void
    {
        $this->word = $this->getRandomWord();
    }

    public function getRandomWord(): string
    {
        $words = [
            'creeper',
            'enderman',
            'minecraft',
            'chicken',
            'microjang'
        ];

        return $words[array_rand($words)];
    }
}