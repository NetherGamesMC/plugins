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
use function implode;

class Server
{
    public const OFFLINE = 0;
    public const ONLINE = 1;
    public const FULL = 2;

    public function __construct(
        private string  $region,
        private Cluster $cluster,
        private string  $replicaId,
        private string  $uniqueDeploymentId
    )
    {
    }

    public static function getServerFromUniqueId(string $uniqueId, Cluster $cluster): ?Server
    {
        [$region, $serverType, $gameType, $replicaId, $uniqueDeploymentId] = explode('-', $uniqueId);

        if ($cluster->getServerType() === $serverType && $cluster->getGameType() === $gameType) {
            return new Server($region, $cluster, $replicaId, $uniqueDeploymentId);
        }

        return null;
    }

    public function equals(Server $server): bool
    {
        return $this->getUniqueId() === $server->getUniqueId();
    }

    public function getUniqueId(): string
    {
        $cluster = $this->getCluster();

        return implode('-', [
            $this->region,
            $cluster->getServerType(),
            $cluster->getGameType(),
            $this->replicaId,
            $this->uniqueDeploymentId
        ]);
    }

    public function getCluster(): Cluster
    {
        return $this->cluster;
    }

    public function getReplicaId(): string
    {
        return $this->replicaId;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getUniqueDeploymentId(): string
    {
        return $this->uniqueDeploymentId;
    }
}