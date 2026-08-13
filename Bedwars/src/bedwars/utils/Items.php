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
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\utils;

use bedwars\shops\menu\ShopMenu;
use bedwars\shops\menu\ShopMenuTagType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

abstract class Items extends \libminigames\utils\Items
{

    public static function getQuickBuyPanes(int $offset): Item
    {
        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY)->asItem();
        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Empty slot!");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . "This is Quick Buy Slot #" . ($offset + 1) . "!",
            TextFormat::RESET . TextFormat::AQUA . "Click this " . TextFormat::GRAY . "to add any item from the shop",
        ]);

        $tag = $item->getNamedTag();
        $tag->setByte(ShopMenuTagType::QUICK_BUY_EMPTY_SLOT->value, 1);
        $tag->setInt(ShopMenu::QUICK_BUY_OFFSET_TAG, $offset);
        $item->setNamedTag($tag);

        return $item;
    }

    public static function getPreQuickBuyRemove(): Item
    {
        $item = VanillaItems::BLAZE_ROD();
        $item->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Remove an item.");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . 'Click here to remove any items',
            TextFormat::RESET . TextFormat::GRAY . "from a Quick Buy slot.",
        ]);

        $tag = $item->getNamedTag();
        $tag->setByte(ShopMenu::QUICK_BUY_REMOVE_TAG, 1);
        $item->setNamedTag($tag);

        return $item;
    }

    public static function getQuickBuyRemove(): Item
    {
        $item = VanillaItems::REDSTONE_DUST();
        $item->setCustomName(TextFormat::RESET . TextFormat::RED . "Removing an item.");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . 'Click any item to remove them',
            TextFormat::RESET . TextFormat::GRAY . "from a Quick Buy slot.",
        ]);
        $item->getNamedTag()->setByte(ShopMenu::QUICK_BUY_REMOVE_TAG, 1);
        return $item;
    }

    public static function getQuickBuyAssignItem(int $offset): Item
    {
        // Quick buy option.
        $item = VanillaItems::BLAZE_POWDER();
        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Assigning quick buy item.");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . 'You are now assigning a',
            TextFormat::RESET . TextFormat::GRAY . 'Quick Buy item for slot ' . TextFormat::AQUA . '#' . ($offset + 1),
            "",
            TextFormat::RESET . TextFormat::YELLOW . 'Click here or Quick Buy',
            TextFormat::RESET . TextFormat::YELLOW . 'item to return back.'
        ]);

        $tag = $item->getNamedTag();
        $tag->setInt("unsetOffset", $offset);
        $item->setNamedTag($tag);

        return $item;
    }

    public static function getQuickBuyItem(): Item
    {
        $item = VanillaItems::NETHER_STAR();
        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . 'Quick Buy');
        $item->setLore([TextFormat::RESET . TextFormat::YELLOW . 'Click to view!']);

        return $item;
    }

}