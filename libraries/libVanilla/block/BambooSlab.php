<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Slab;

class BambooSlab extends Slab
{
    public function getFuelTime(): int
    {
        return 150;
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
