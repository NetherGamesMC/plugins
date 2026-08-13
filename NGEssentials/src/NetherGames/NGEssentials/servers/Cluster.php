<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\servers;

use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_values;
use function explode;
use function in_array;

class Cluster
{
    /** @var int */
    private int $onlinePlayers = 0;
    /** @var int */
    private int $maxPlayers = 0;
    /** @var string */
    private string $serverType;
    /** @var string */
    private string $gameType;
    /** @var Player[] */
    private array $queue = [];

    public function __construct(private string $uniqueId)
    {
        [$serverType, $gameType] = explode('-', $uniqueId);

        $this->serverType = $serverType;
        $this->gameType = $gameType;
    }

    public function getName(): string
    {
        $serverType = ServerManager::getName($this->getServerType());

        if (($gameType = $this->getGameType()) === '') {
            return $serverType;
        }

        return $serverType . '-' . $gameType;
    }

    /**
     * @return string
     */
    public function getServerType(): string
    {
        return $this->serverType;
    }

    public function getGameType(): string
    {
        return $this->gameType;
    }

    public function getServer(string $serverUniqueId): ?Server
    {
        return Server::getServerFromUniqueId($serverUniqueId, $this);
    }

    public function updateStatus(int $onlinePlayers, int $maxPlayers): void
    {
        $this->onlinePlayers = $onlinePlayers;
        $this->maxPlayers = $maxPlayers;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @return Player[]
     */
    public function getQueuedPlayers(): array
    {
        return array_values($this->queue);
    }

    public function removeFromQueue(Player $player): void
    {
        $this->queue = array_diff($this->queue, [$player]);
    }

    public function addToQueue(Player $player): void
    {
        $this->queue[] = $player;
    }

    public function isInQueue(Player $player): bool
    {
        return in_array($player, $this->queue, true);
    }

    public function getStringStatus(Player $player, ?DataServer $server = null): string
    {
        if ($server === null) {
            $onlinePlayers = $this->getOnlinePlayers();
        } else {
            $onlinePlayers = $server->getOnlinePlayers();
        }

        return match ($this->getStatus($server)) {
            Server::ONLINE => TextFormat::DARK_GREEN . $onlinePlayers . ' playing',
            Server::FULL => (ServerManager::canJoinFullServers($player) ? TextFormat::RED . $onlinePlayers . ' playing' : Translator::getTranslationPlayer($player, "forms.teleporter.full", Translator::TYPE_ERROR)),
            default => Translator::getTranslationPlayer($player, "forms.teleporter.offline", Translator::TYPE_ERROR),
        };
    }

    public function getOnlinePlayers(): int
    {
        return $this->onlinePlayers;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function getStatus(?DataServer $server = null): int
    {
        if ($server === null) {
            $onlinePlayers = $this->getOnlinePlayers();
            $maxPlayers = $this->getMaxPlayers();
        } else {
            $onlinePlayers = $server->getOnlinePlayers();
            $maxPlayers = $server->getMaxPlayers();
        }

        if ($maxPlayers === 0) {
            return Server::OFFLINE;
        }

        if ($onlinePlayers >= $maxPlayers) {
            return Server::FULL;
        }

        return Server::ONLINE;
    }
}