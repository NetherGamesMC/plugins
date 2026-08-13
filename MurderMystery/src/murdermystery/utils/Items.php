<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
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

namespace murdermystery\utils;

use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

final class Items extends \libminigames\utils\Items
{
    public const ROLE_ASSIGNER_SLOT = 0;

    public const MURDERER_SWORD_SLOT = 1;
    public const MURDERER_ARROW_SLOT = 2;

    public const DETECTIVE_BOW_SLOT = 1;
    public const DETECTIVE_ARROW_SLOT = 9;

    public const INNOCENT_BOW_SLOT = 0;
    public const INNOCENT_ARROW_SLOT = 1;

    public const ALPHA_SWORD_SLOT = 1;
    public const ALPHA_BOW_SLOT = 0;

    public const INFECTED_SWORD_SLOT = 0;

    public const SURVIVOR_BOW_SLOT = 0;
    public const SURVIVOR_ARROW_SLOT = 1;

    public const COMPASS = 4;
    public const RESOURCE_SLOT = 8;

    public static function getInnocentLocator(): Item
    {
        return VanillaItems::COMPASS()->setCustomName('§r§aInnocents Locator')->setLore(['§r§7Use it to locate remaining players']);
    }

    public static function getDetectiveBow(): Item
    {
        return VanillaItems::BOW()->setCustomName('§r§aBow')->setLore(['§r§7Use your Bow to kill the Murderer']);
    }

    public static function getBowLocator(): Item
    {
        return VanillaItems::COMPASS()->setCustomName('§r§aBow Locator')->setLore(['§r§7Use this compass to locate the Bow']);
    }

    public static function getBow(): Item
    {
        return VanillaItems::BOW()->setCustomName('§r§aBow')->setLore(['§r§7Use this Bow to defend yourself from the Murderer']);
    }

    public static function getFakeBow(): Item
    {
        return VanillaItems::BOW()->setCustomName('§r§cFake Bow');
    }

    public static function getKnife(Player $player): Item
    {
        return (CosmeticHandler::KNIFES()->get($player) ?? VanillaItems::IRON_SWORD())->setCustomName('§r§aKnife')->setLore(['§r§7Use your Knife to kill players.']);
    }

    public static function getResourceItem(): Item
    {
        return VanillaItems::GOLD_INGOT();
    }
}