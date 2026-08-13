<?php
declare(strict_types=1);

namespace lobby\features\activity;

use pocketmine\player\Player;

class ActivityManager
{
    /** @var ActivityIntent[] */
    private array $runningActivities = [];

    public function startActivity(ActivityIntent $intent): void
    {
        if (array_key_exists($intent->getPlayer(), $this->runningActivities)) {
            // Previous activity is still running
            $this->runningActivities[$intent->getPlayer()]->exit(false);
        }

        $this->runningActivities[$intent->getPlayer()] = $intent;
    }

    public function removeFromActivity(Player $player, bool $isDisconnect = false): void
    {
        if (array_key_exists($player->getXuid(), $this->runningActivities)) {
            $this->runningActivities[$player->getXuid()]->exit($isDisconnect);
        }
    }

    public function endActivity(Player $player): void
    {
        unset($this->runningActivities[$player->getXuid()]);
    }
}