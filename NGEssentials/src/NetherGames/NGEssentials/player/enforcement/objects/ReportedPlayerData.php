<?php

namespace NetherGames\NGEssentials\player\enforcement\objects;

use libReplay\session\record\RecordManager;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;

class ReportedPlayerData
{
    private ?int $replayId = null;

    public function __construct(private IPlayer $player, private string $xuid)
    {
        if ($player instanceof Player && $player->isConnected()) {
            $this->replayId = RecordManager::getInstance()?->getRecording($player->getWorld())?->getReplayId();
        }
    }

    public static function fromPlayer(Player $player): self
    {
        return new self($player, $player->getXuid());
    }

    public function getDisplayName(): string
    {
        return $this->player instanceof Player ? NGEssentials::getInstance()->getPlayerManager()->getPlayerName($this->player) : $this->player->getName();
    }

    public function getXuid(): string
    {
        return $this->xuid;
    }

    public function getReplayId(): ?int
    {
        return $this->replayId;
    }

    public function getPlayer(): IPlayer
    {
        return $this->player;
    }
}