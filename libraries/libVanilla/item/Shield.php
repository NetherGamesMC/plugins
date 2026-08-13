<?php

namespace libVanilla\item;

use pocketmine\item\Durable;

class Shield extends Durable
{
    public function getMaxDurability(): int
    {
        return 337;
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }
}