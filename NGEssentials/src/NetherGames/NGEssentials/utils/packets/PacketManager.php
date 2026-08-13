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

namespace NetherGames\NGEssentials\utils\packets;

use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;
use function array_diff;
use function array_key_first;
use function array_merge;
use function count;

class PacketManager
{
    /** @var self[] */
    private static array $packetQueues = [];
    /** @var int */
    private static int $queueCount = -1;
    /** @var Player[] */
    private array $players = [];
    /** @var ClientboundPacket[] */
    private array $packets = [];

    public static function new(): int
    {
        $id = ++self::$queueCount;

        self::$packetQueues[$id] = new self();

        return $id;
    }

    public static function deliverPackets(int $id = -1, bool $immediate = false, bool $remove = true): void
    {
        self::getPacketQueue($id)->deliver($immediate, !$remove);

        if ($remove) {
            if ($id === -1) {
                unset(self::$packetQueues[self::$queueCount]);
            } else {
                unset(self::$packetQueues[$id]);
            }
        }
    }

    public function deliver(bool $immediate = false, bool $reset = false): void
    {
        if (count($this->getPackets()) !== 0) {
            $count = count($players = $this->getPlayers());

            if ($count > 0) {
                $player = $players[array_key_first($players)];

                if ($count === 1) {
                    $networkSession = $player->getNetworkSession();

                    foreach ($this->getPackets() as $packet) {
                        $networkSession->sendDataPacket($packet, $immediate);
                    }
                } else {
                    NetworkBroadcastUtils::broadcastPackets($this->getPlayers(), $this->getPackets());
                }
            }
        }

        if ($reset) {
            $this->packets = [];
        }
    }

    /**
     * @return ClientboundPacket[]
     */
    private function getPackets(): array
    {
        return $this->packets;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param Player[] $players
     */
    public function setPlayers(array $players): void
    {
        $this->players = $players;
    }

    public static function getPacketQueue(int $id = -1): self
    {
        if ($id === -1) {
            return self::$packetQueues[self::$queueCount];
        }

        return self::$packetQueues[$id];
    }

    /**
     * @param Player $player
     */
    public function addPlayer(Player $player): void
    {
        $this->players[] = $player;
    }

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void
    {
        $this->players = array_diff($this->players, [$player]);
    }

    /**
     * @param ClientboundPacket[] $packets
     */
    public function addPackets(array $packets): void
    {
        $this->packets = array_merge($this->packets, $packets);
    }

    public function addPacket(ClientboundPacket $packet): void
    {
        $this->packets[] = $packet;
    }
}
