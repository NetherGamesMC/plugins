<?php

namespace libVanilla\item;

use pocketmine\item\Minecart;

class ChestMinecart extends Minecart
{
    public function getMaxStackSize(): int
    {
        return 1;
    }
}