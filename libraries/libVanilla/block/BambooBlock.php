<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Wood;

class BambooBlock extends Wood
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
        return 5;
    }

    public function isLog(): bool
    {
        return true;
    }
}
