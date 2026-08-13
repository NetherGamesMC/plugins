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

use mommasays\games\listener\GameListener;
use mommasays\MSArena;
use mommasays\utils\StatsData;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class Game extends GameListener
{

    public const ARENA_SPAWN_POINT = [1, 50, 1];
    public const MINIGAMES = [
        GameArmorStand::class,
        GameBreakBlock::class,
        GameClickFlower::class,
        GameEquation::class,
        GameJump::class,
        GameJumpToVoid::class,
        GameKeepMove::class,
        GameMineCobble::class,
        GameNoMove::class,
        GamePlace::class,
        GamePunch::class,
        GameStandBlock::class,
        GameTypeChat::class,
        GameWitch::class,
    ];

    /** @var MSArena */
    private MSArena $arena;
    /** @var string[] */
    private array $winners = [];
    /** @var string[] */
    private array $losers = [];
    /** @var string */
    private string $first = '';

    public function __construct(MSArena $arena, bool $teleport)
    {
        $this->arena = $arena;

        if ($teleport) {
            foreach ($arena->getPlayers() as $player) {
                $player->teleport(new Vector3(self::ARENA_SPAWN_POINT[0], self::ARENA_SPAWN_POINT[1], self::ARENA_SPAWN_POINT[2]));
            }
        }
    }

    abstract public function getMessage(): string;

    public function setupArena(): void
    {

    }

    public function resetArena(): void
    {

    }

    public function finishGame(): void
    {
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            if (!$this->isWinner($player->getName())) {
                $this->addLoser($player);
            }
        }
    }

    public function getArena(): MSArena
    {
        return $this->arena;
    }

    /**
     * @param string $player
     * @return bool
     */
    public function isWinner(string $player): bool
    {
        return in_array($player, $this->winners, true);
    }

    /**
     * @param Player $player
     * Adds a player to the list of losers
     * Clears all Inventories, plays loser sound
     * teleports the player to the loser cage if required by the Game
     */
    public function addLoser(Player $player): void
    {
        $this->losers[] = $player->getName();
        $this->getArena()->broadcastMessage($player->getNameTag() . TextFormat::RED . ' failed the game.');

        $this->getArena()->getStatsData()->addValue($player, StatsData::MS_FAILS);

        if ($this->isUsingCages()) {
            $player->teleport($this->getArena()->getLoserSpawn());
        }

        $player->setGamemode(GameMode::ADVENTURE);

        $player->getInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getArmorInventory()->clearAll();

        $pos = $player->getLocation();
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            "random.didgeridoo",
            $pos->getX(),
            $pos->getY(),
            $pos->getZ(),
            100,
            1,
            null
        ));
    }

    /**
     * @return bool
     * If this returns true, it will teleport the player on win / lose to the cages and on the next game it will teleport them back down
     */
    public function isUsingCages(): bool
    {
        return false;
    }

    public function addWinner(Player $player): void
    {
        $this->winners[] = $player->getName();
        $this->getArena()->broadcastMessage($player->getNameTag() . TextFormat::GREEN . ' finished the game.');

        $this->getArena()->getStatsData()->addValue($player, StatsData::MS_SUCCESSES);

        $this->getArena()->increaseGamesWon($player);
        $this->getArena()->increasePoints($player);
        if ($this->setFirst($player->getNameTag())) {
            $this->getArena()->increasePoints($player);
        }

        if ($this->isUsingCages()) {
            $player->teleport($this->getArena()->getWinnerSpawn());
        }

        $player->setGamemode(GameMode::ADVENTURE);

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getCursorInventory()->clearAll();

        $pos = $player->getLocation();
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            "random.levelup",
            $pos->getX(),
            $pos->getY(),
            $pos->getZ(),
            100,
            1,
            null
        ));
    }

    public function isLoser(string $player): bool
    {
        return in_array($player, $this->losers, true);
    }

    public function getFirst(): ?string
    {
        return $this->first;
    }

    public function setFirst(string $player): bool
    {
        if ($this->first !== '') {
            return false;
        }

        $this->first = $player;
        return true;
    }
}