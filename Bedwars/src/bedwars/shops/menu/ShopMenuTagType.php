<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
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

namespace bedwars\shops\menu;

enum ShopMenuTagType: string
{
    /** The tag associated with clickable items that reveal the contents of a category */
    case CATEGORY_DISPLAY = "categoryDisplayTag";
    /** The tag associated with a specified shop item inside a category */
    case SHOP_ITEM = "categoryItemTag";
    /** The tag associated with an empty slot inside the quick-buy menu */
    case QUICK_BUY_EMPTY_SLOT = "quickBuyEmptySlotTag";
    /** The tag associated with an individual shop item set inside the quick-buy menu */
    case QUICK_BUY_ITEM = "quickBuyItemTag";
    /** This case is only used when an item does not match any of the above */
    case UNKNOWN = "unknownTag";
}