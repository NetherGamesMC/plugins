<?php

declare(strict_types=1);

namespace libVanilla\block\blockscheduler;

use pocketmine\math\Vector3;
use pocketmine\world\World;

interface BlockScheduler
{

    public function scheduleDelayedBlockUpdate(World $world, Vector3 $pos, int $min_delay): int;

    public function destroy(): void;
}