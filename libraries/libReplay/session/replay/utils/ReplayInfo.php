<?php

namespace libReplay\session\replay\utils;

use DateTime;

class ReplayInfo
{
    private DateTime $time;

    /**
     * @param string[] $players
     */
    private function __construct(private int $replayId, private int $protocolId, private string $serverType, private string $gameType, private string $mapName, private array $players, int $time)
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($time);

        $this->time = $dateTime;
    }

    /**
     * @param string[] $players
     */
    public static function create(int $replayId, int $protocolId, string $serverType, string $gameType, string $mapName, array $players, int $time): self
    {
        return new self($replayId, $protocolId, $serverType, $gameType, $mapName, $players, $time);
    }

    public function getGameType(): string
    {
        return $this->gameType;
    }

    public function getReplayId(): int
    {
        return $this->replayId;
    }

    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    /**
     * @return string[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getServerType(): string
    {
        return $this->serverType;
    }

    public function getTime(): DateTime
    {
        return $this->time;
    }

    public function getMapName(): string
    {
        return $this->mapName;
    }
}