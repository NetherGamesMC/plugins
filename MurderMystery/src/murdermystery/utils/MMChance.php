<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace murdermystery\utils;

use murdermystery\gamemodes\MMArena;
use pocketmine\player\Player;

final class MMChance
{
    public const MURDERER = 0;
    public const DETECTIVE = 1;
    public const INFECTED = 2;

    /** @var array<string, array<int>> */
    private array $playerChances = [];
    /** @var array<int, array<int>> */
    private array $arenaChances = [];

    private function increaseArenaChance(MMArena $arena, int $type, int $amount): void
    {
        $this->arenaChances[$arena->getId()][$type] = ($this->arenaChances[$arena->getId()][$type] ?? 0) + $amount;
    }

    public function removeArenaChance(MMArena $arena): void
    {
        unset($this->arenaChances[$arena->getId()]);
    }

    public function addArenaPlayer(MMArena $arena, Player $player): void
    {
        foreach ($arena->getModeId() === MMArena::MODE_CLASSIC ? [self::MURDERER, self::DETECTIVE] : [self::INFECTED] as $type) {
            $this->increaseArenaChance($arena, $type, $this->getChance($player, $type));
        }
    }

    public function getChance(Player $player, int $type): int
    {
        return $this->playerChances[$player->getName()][$type] ?? 1;
    }

    public function getChancePercentage(MMArena $arena, Player $player, int $type): int
    {
        if (($chance = $this->getArenaChance($arena, $type)) === 0) {
            return 100;
        }

        return (int)(($this->getChance($player, $type) / $chance) * 100);
    }

    public function getArenaChance(MMArena $arena, int $type): int
    {
        return $this->arenaChances[$arena->getId()][$type] ?? 0;
    }

    public function removePlayerChance(MMArena $arena, Player $player): void
    {
        foreach ($arena->getModeId() === MMArena::MODE_CLASSIC ? [self::MURDERER, self::DETECTIVE] : [self::INFECTED] as $type) {
            $this->increaseArenaChance($arena, $type, -$this->getChance($player, $type));
        }
    }

    public function addPlayerChange(Player $player, int $type, int $value): void
    {
        if ($player->hasPermission('nethergames.executive')) {
            $value *= 5;
        } elseif ($player->hasPermission('nethergames.vip.legend')) {
            $value *= 2;
        }

        $this->playerChances[$player->getName()][$type] = ($this->playerChances[$player->getName()][$type] ?? 0) + $value;
    }

    public function setPlayerChance(Player $player, int $type, int $value = 0): void
    {
        $this->playerChances[$player->getName()][$type] = $value;
    }

    public function getHighestChances(MMArena $arena): array
    {
        if ($arena->getModeId() === MMArena::MODE_CLASSIC) {
            $murderChance = 0;
            $murder = null;
            $detectiveChance = 0;
            $detective = null;

            $players = $arena->getAlivePlayers();
            foreach ($players as $player) {
                if (($chance = $this->getChance($player, self::MURDERER)) > $murderChance) {
                    $murder = $player;
                    $murderChance = $chance;
                }
            }

            unset($players[array_search($murder, $players, true)]);

            foreach ($players as $player) {
                if (($chance = $this->getChance($player, self::DETECTIVE)) > $detectiveChance) {
                    $detective = $player;
                    $detectiveChance = $chance;
                }
            }

            return [$murder, $detective];
        }

        if ($arena->getModeId() === MMArena::MODE_INFECTION) {
            $infectedChance = 0;
            $infected = null;

            foreach ($arena->getAlivePlayers() as $player) {
                if (($chance = $this->getChance($player, self::INFECTED)) > $infectedChance) {
                    $infected = $player;
                    $infectedChance = $chance;
                }
            }

            return [$infected];
        }

        return [];
    }
}