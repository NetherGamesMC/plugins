<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils;

enum PlayerCosmeticStatus: int
{
    case SELECTED = 0;
    case UNLOCKED = 1;
    case LOCKED = 2;
}