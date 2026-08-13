<?php

/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author cooldogedev
 *
 */

declare(strict_types=1);

namespace murdermystery\utils;

use libforms\elements\Dropdown;
use libforms\FormManager;
use murdermystery\gamemodes\classic\MMArenaClassic;
use murdermystery\gamemodes\infection\MMArenaInfection;
use murdermystery\gamemodes\MMArena;
use pocketmine\player\Player;
use function array_search;
use function shuffle;

final class Forms extends \libminigames\utils\Forms
{

}
