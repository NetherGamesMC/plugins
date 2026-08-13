<?php
declare(strict_types=1);

namespace lobby\features\checkpoint;

use lobby\utils\PlayerUtils;
use pocketmine\player\Player;

class CheckpointManager
{
    private array $checkpointPlayers = [];

    public function reachCheckpoint(Player $player, string $checkpointName): void
    {
        if (array_key_exists(key: $player->getName(), array: $this->checkpointPlayers)) {
            $this->checkpointPlayers[$player->getName()][] = $checkpointName;
        } else {
            $this->checkpointPlayers[$player->getName()] = [$checkpointName];
        }

        $player->sendTitle("§aCheckpoint reached");
        PlayerUtils::playSound($player, "random.levelup", 1);
    }

    public function hasReachedCheckpoint(Player $player, string $checkpointName): bool
    {
        if (!array_key_exists(key: $player->getName(), array: $this->checkpointPlayers)) return false;

        return in_array(needle: $checkpointName, haystack: $this->checkpointPlayers[$player->getName()]);
    }
}