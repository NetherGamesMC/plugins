<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author MegaRabyteYT
 *
 */
declare(strict_types=1);

namespace bedwars\utils\world;

use bedwars\BWArena;
use bedwars\BWItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_pop;
use function min;
use function round;
use function shuffle;

class PlayerSwapUtils
{
    private const int GAME_START_DELAY = 60;

    private int $minSwapDelay = 60 * 3;
    private int $maxSwapDelay = 60 * 7;

    private ?int $posSwapDelay = null;

    public function __construct(?int $minSwapDelay = null, ?int $maxSwapDelay = null)
    {
        $this->setSwapDelays($minSwapDelay, $maxSwapDelay);
    }

    public function setSwapDelays(?int $minSwapDelay = null, ?int $maxSwapDelay = null): void
    {
        $this->minSwapDelay = $minSwapDelay ?? $this->minSwapDelay;
        $this->maxSwapDelay = $maxSwapDelay ?? $this->maxSwapDelay;
    }

    public function handle(int $timePassed, BWArena $arena): void
    {
        if ($timePassed < self::GAME_START_DELAY) {
            foreach ($arena->getPlayers() as $player) {
                if (!$player->getArmorInventory()->getHelmet()->equals(BWItems::LEVITATION_HELMET())) {
                    $player->getXpManager()->setXpAndProgress(0,
                        1 - round(min(($timePassed / self::GAME_START_DELAY), 1), 2)
                    );
                }
            }
        } elseif ($timePassed === self::GAME_START_DELAY) {
            $arena->broadcastTitle(TextFormat::GREEN . "PLAYER SWAP ACTIVE!", "You will now randomly swap with enemy players. Be careful!", 0, 20, 20);

            foreach ($arena->getPlayers() as $player) {
                if (!$player->getArmorInventory()->getHelmet()->equals(BWItems::LEVITATION_HELMET())) {
                    $player->getXpManager()->setXpAndProgress(0, 0);
                }
            }

            $this->posSwapDelay = null;
        } elseif ($this->posSwapDelay === null) {
            $this->posSwapDelay = random_int($this->minSwapDelay, $this->maxSwapDelay);
        } elseif ($this->posSwapDelay > 0) {
            if ($this->posSwapDelay === 2) {
                $arena->broadcastTitle(TextFormat::RED . "SWAP IMMINENT", 'Prepare yourself!', 2, 15, 5);
            }

            $this->posSwapDelay--;
        } else {

            $this->swapPlayers($arena);
            $this->posSwapDelay = null;
        }
    }

    private function swapPlayers(BWArena $arena): void
    {
        foreach ($this->matchPlayers($arena) as [$player1, $player2]) {
            $loc1 = $player1->getPosition();
            $loc2 = $player2->getPosition();

            if ($player1 === $player2) {
                $player1->sendTitle(TextFormat::GREEN . "Phew", "No swap for you. Keep playing!");
            } else {
                $player1->sendTitle(TextFormat::GREEN . "SWAPPED", "You swapped with {$player2->getNameTag()}");
                $player2->sendTitle(TextFormat::GREEN . "SWAPPED", "You swapped with {$player1->getNameTag()}");

                $player1->teleport($loc2);
                $player2->teleport($loc1);
            }
        }
    }

    /**
     * @return list<list{Player, Player}> Array of pairs of players to be swapped.
     */
    private function matchPlayers(BWArena $arena): array
    {
        $availablePlayers = array_filter($arena->getAlivePlayers(), fn(Player $player) => $player->isSurvival(true));
        shuffle($availablePlayers);

        /** @var list<list{Player, Player}> $matches */
        $matches = [];
        while (count($availablePlayers) > 1) {
            $matches[] = [array_pop($availablePlayers), array_pop($availablePlayers)];
        }

        if (count($availablePlayers) === 1) {
            $lastPlayerXuid = array_pop($availablePlayers);
            $matches[] = [$lastPlayerXuid, $lastPlayerXuid];
        }

        return $matches;
    }
}