<?php

declare(strict_types=1);

namespace bridges\utils;

use NetherGames\NGEssentials\item\CustomItemRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Items extends \libminigames\utils\Items
{
    public const NINTENDO_BUTTON_UP = "\u{E04C}";
    public const NINTENDO_BUTTON_DOWN = "\u{E04E}";

    public static function getSaveLayout(): Item
    {
        $item = VanillaBlocks::CHEST()->asItem();
        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Save Layout");
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . 'Save your inventory layout for',
            TextFormat::RESET . TextFormat::GREEN . 'NetherGames The Bridge.',
            TextFormat::RESET . TextFormat::GREEN . '',
            TextFormat::RESET . TextFormat::YELLOW . 'Click to save!'
        ]);

        return $item;
    }

    public static function getResetLayout(): Item
    {
        $item = VanillaBlocks::BARRIER()->asItem();
        $item->setCustomName(TextFormat::RESET . TextFormat::RED . 'Reset Layout');
        $item->setLore([
            TextFormat::RESET . TextFormat::GRAY . 'Reset your inventory layout for',
            TextFormat::RESET . TextFormat::GREEN . 'NetherGames The Bridge.',
            TextFormat::RESET . TextFormat::GREEN . '',
            TextFormat::RESET . TextFormat::YELLOW . 'Click to reset!'
        ]);

        return $item;
    }

    public static function getInventorySeparator(): Item
    {
        $item = VanillaBlocks::IRON_BARS()->asItem();
        $item->setCustomName(TextFormat::RESET . TextFormat::GRAY .
            self::NINTENDO_BUTTON_UP . ' Inventory' . TextFormat::EOL . TextFormat::EOL .
            self::NINTENDO_BUTTON_DOWN . ' Hotbar');

        return $item;
    }

    public static function getPreferencesSelector(): Item
    {
        $item = CustomItemRegistry::LAYOUT_EDITOR();
        $item->setCustomName(TextFormat::RESET . TextFormat::GREEN . 'Edit Layout ' . TextFormat::GRAY . "(Right-Click)");
        $item->setLore(['§r§7Right-click to edit your inventory layout!']);

        return $item;
    }

}