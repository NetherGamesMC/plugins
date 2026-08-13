<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils;

use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;

class PlayerCosmeticEntry
{
    public function __construct(
        public readonly CosmeticEntry        $entry,
        public readonly PlayerCosmeticStatus $status,
    )
    {
    }
}