<?php
declare(strict_types=1);

namespace lobby\entity\custom;

use pocketmine\entity\object\ItemEntity;
use pocketmine\player\Player;

class StaticItem extends ItemEntity
{
    private array $players = [];

    public function spawnTo(Player $player): void
    {
        if (in_array($player->getId(), $this->players)) {
            parent::spawnTo($player);
        }
    }

    public function hasGravity(): bool
    {
        return false;
    }

    public function addPlayer(Player $player): void
    {
        $this->players[] = $player->getId();
    }
}
