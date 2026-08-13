<?php

declare(strict_types=1);

namespace libReplay\session\replay\tasks;

use libReplay\session\replay\ReplayManager;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class ReplayTickTask extends Task
{
    private int $tick = 0;

    public function __construct(private ReplayManager $manager)
    {
    }

    public function onRun(): void
    {
        $shouldUpdateScoreboard = $this->tick % 20 === 0;

        foreach ($this->manager->getReplays() as $replay) {
            $replay->tick();

            if ($shouldUpdateScoreboard) {
                $timeInSeconds = (int)($replay->getTick() / 20);
                $minutes = (int)($timeInSeconds / 60);
                $seconds = $timeInSeconds % 60;
                $secondsString = $seconds >= 10 ? (string)$seconds : "0$seconds";

                $this->manager->getScoreboard()->setLine($replay->getWorld()->getPlayers(), 5, "Time: " . TextFormat::GREEN . $minutes . ":" . $secondsString);
            }
        }

        $this->tick++;
    }
}