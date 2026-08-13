<?php

namespace libVanilla\item;

use pocketmine\item\Durable;

class ElytraItem extends Durable
{
    public function getMaxDurability(): int
    {
        return 432;
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }
}