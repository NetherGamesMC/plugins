<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Door;

class BambooDoor extends Door
{
    public function getFuelTime(): int
    {
        return 200;
    }

    public function getFlameEncouragement(): int
    {
        return 5;
    }

    public function getFlammability(): int
    {
        return 20;
    }
}
