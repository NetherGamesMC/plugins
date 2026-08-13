<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Button;

class BambooButton extends Button
{
    public function getFuelTime(): int
    {
        return 100;
    }

    public function getFlameEncouragement(): int
    {
        return 5;
    }

    public function getFlammability(): int
    {
        return 20;
    }

    protected function getActivationTime(): int
    {
        return 30;
    }
}
