<?php

declare(strict_types=1);

namespace lobby\features\zone\types;

use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\player\worldfeatures\zones\Zone;
use pocketmine\player\Player;

class InvisiblityZone extends Zone
{
    use BaseTrait;

    public function enter(Player $player): void
    {
        foreach ($player->getServer()->getOnlinePlayers() as $p) {
            $p->hidePlayer($player);
        }
    }

    public function leave(Player $player): void
    {
        $this->getNGEssentials()->getPlayerManager()->updatePlayerVisibility($player);
    }
}