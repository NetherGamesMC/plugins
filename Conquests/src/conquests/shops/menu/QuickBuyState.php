<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace conquests\shops\menu;

enum QuickBuyState
{
    /** The state where a player is adding an item to their quick-buy menu */
    case ADD_ITEM;
    /** The state where a player is removing an item from their quick-buy menu */
    case REMOVE_ITEM;
    /** The default state where a player is not modifying their quick-buy menu */
    case NORMAL;
}