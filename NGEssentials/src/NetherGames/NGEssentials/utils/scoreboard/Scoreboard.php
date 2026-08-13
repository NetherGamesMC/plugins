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

namespace NetherGames\NGEssentials\utils\scoreboard;

use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\{ClientboundPacket,
    RemoveObjectivePacket,
    SetDisplayObjectivePacket,
    SetScorePacket};
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use function array_key_first;
use function count;
use function str_repeat;

class Scoreboard
{
    /** @var string */
    private string $objectiveName;

    public function __construct(private string $displayName, private string $displaySlot, private int $sortOrder)
    {
        $this->objectiveName = uniqid('', true);
    }

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(RemoveObjectivePacket::create($this->objectiveName));
    }

    public function addPlayer(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(SetDisplayObjectivePacket::create(
            $this->displaySlot,
            $this->objectiveName,
            $this->displayName,
            'dummy',
            $this->sortOrder
        ));
    }

    public function removeLine(array $players, int $line): void
    {
        $this->setLines($players, [$line => ''], true);
    }

    /**
     * @param Player[] $players
     * @param array $lines
     * @param bool $reset
     */
    public function setLines(array $players, array $lines, bool $reset = false): void
    {
        $count = count($players);

        if ($count > 0) {
            $packets = [];

            $packets[] = $this->getPacket($lines, SetScorePacket::TYPE_REMOVE);
            if (!$reset) {
                $packets[] = $this->getPacket($lines, SetScorePacket::TYPE_CHANGE);
            }

            $player = $players[array_key_first($players)];

            if ($count === 1) {
                $networkSession = $player->getNetworkSession();

                foreach ($packets as $packet) {
                    $networkSession->sendDataPacket($packet);
                }
            } else {
                NetworkBroadcastUtils::broadcastPackets($players, $packets);
            }
        }
    }

    public function getPacket(array $lines, int $type): ClientboundPacket
    {
        $entries = [];

        foreach ($lines as $line => $message) {
            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->objectiveName;
            $entry->score = $line;
            $entry->scoreboardId = $line;

            if ($type === SetScorePacket::TYPE_CHANGE) {
                if ($message === '') {
                    $message = str_repeat(' ', $line - 1);
                }

                $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
                $entry->customName = $message . ' ';
            }

            $entries[] = $entry;
        }

        return SetScorePacket::create(
            $type,
            $entries
        );
    }

    /**
     * @param Player[] $players
     * @param int $line
     * @param string $message
     * @param bool $reset
     */
    public function setLine(array $players, int $line, string $message = '', bool $reset = false): void
    {
        $this->setLines($players, [$line => $message], $reset);
    }
}
