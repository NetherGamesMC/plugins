<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\SimplePressurePlate;

class BambooPressurePlate extends SimplePressurePlate
{
    public function getFuelTime(): int
    {
        return 300;
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
