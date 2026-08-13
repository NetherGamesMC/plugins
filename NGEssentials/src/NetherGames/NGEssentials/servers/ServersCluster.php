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

class ServersCluster extends Cluster
{
    /** @var DataServer[] */
    private array $servers = [];

    public function updateServers(array $servers): void
    {
        $this->servers = [];

        // Creates new servers all the time so the serverOrder is always the same on all servers
        foreach ($servers as $serverUniqueId => $data) {
            $playerCount = $data['count'];
            $maxPlayers = $data['max'];

            $server = DataServer::getServerFromUniqueId($serverUniqueId, $this);
            if ($server !== null) {
                $server->setMaxPlayers($maxPlayers);
                $server->updatePlayerCount($playerCount);

                $this->servers[$serverUniqueId] = $server;
            }
        }
    }

    public function getServer(string $serverUniqueId): ?Server
    {
        return $this->servers[$serverUniqueId] ?? parent::getServer($serverUniqueId);
    }

    /**
     * @return DataServer[]
     */
    public function getServers(): array
    {
        return $this->servers;
    }
}