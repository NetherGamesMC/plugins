<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
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

namespace NetherGames\NGEssentials\player\enforcement\objects;

use NetherGames\NGEssentials\servers\Server;
use pocketmine\player\IPlayer;

class StaffPortalInfo
{

    public function __construct(
        private IPlayer $player,
        private string  $ip,
        private string  $xuid,
        private string  $deviceId,
        private ?Server $server = null,
        private string  $proxy = '')
    {
    }

    public function getProxy(): string
    {
        return $this->proxy;
    }

    public function getPlayer(): IPlayer
    {
        return $this->player;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getXuid(): string
    {
        return $this->xuid;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }
}