<?php

declare(strict_types=1);

namespace lobby\utils;

use lobby\Lobby;
use NetherGames\NGEssentials\NGEssentials;

trait BaseTrait
{
    public function getNGEssentials(): NGEssentials
    {
        return NGEssentials::getInstance();
    }

    public function getPlugin(): Lobby
    {
        return Lobby::getInstance();
    }
}