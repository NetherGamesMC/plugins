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

use function explode;

class DataServer extends Server
{
    /** @var int */
    private int $onlinePlayers = 0;
    /** @var int */
    private int $maxPlayers = 0;

    public static function getServerFromUniqueId(string $uniqueId, Cluster $cluster): ?DataServer
    {
        [$region, $serverType, $gameType, $replicaId, $uniqueDeploymentId] = explode('-', $uniqueId);

        if ($cluster->getServerType() === $serverType && $cluster->getGameType() === $gameType) {
            return new DataServer($region, $cluster, $replicaId, $uniqueDeploymentId);
        }

        return null;
    }

    public function updatePlayerCount(int $playerCount): void
    {
        $this->onlinePlayers = $playerCount;
    }

    public function getOnlinePlayers(): int
    {
        return $this->onlinePlayers;
    }

    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): void
    {
        $this->maxPlayers = $maxPlayers;
    }
}