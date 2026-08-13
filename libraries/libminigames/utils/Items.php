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
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\utils;

use libminigames\Arena;
use libminigames\TeamArena;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class Items
{
    public const TEAM_SELECTOR = 0;
    public const EXTRA_SOLO_ITEM_0 = 0;

    public const PRIVATE_GAME_SETTINGS = 1;

    public const EXTRA_ITEM_1 = 2;

    public const MAP_SELECTOR = 4;

    public const EXTRA_ITEM_2 = 6;

    public const EXTRA_ITEM_3 = 3;

    public const QUIT_BED = 8;

    public static function getCompass(): Item
    {
        return VanillaItems::COMPASS()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Player Locator");
    }

    public static function getSpectatorCompass(): Item
    {
        return CustomItemRegistry::TELEPORTER()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Teleporter");
    }

    public static function getQuitBed(?DyeColor $color = null): Item
    {
        return CustomItemRegistry::LEAVE()->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Return to Lobby");
    }

    public static function getTeamSelectionWool(?DyeColor $color): Item
    {
        return CustomItemRegistry::TEAM_SELECTOR($color ??= DyeColor::RED())->setCustomName(TextFormat::RESET . Utils::getTextColorByDyeColor($color) . TextFormat::BOLD . 'Change Team');
    }

    public static function getTeamSelectionWoolByPlayer(Player $player, Arena $arena): Item
    {
        if (!$arena instanceof TeamArena || $arena->isSpectator($player)) {
            return self::getTeamSelectionWool(DyeColor::LIGHT_GRAY());
        } else {
            return self::getTeamSelectionWool($arena->getTeam($player)->getDyeColor());
        }
    }

    public static function getMapSelectionPaper(): Item
    {
        return CustomItemRegistry::ZONES()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Map Selector");
    }

    public static function getReplayPaper(): Item
    {
        return CustomItemRegistry::PLAY_AGAIN()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Play Again");
    }

    public static function getTypeSelectionAnvil(): Item
    {
        return CustomItemRegistry::SCENARIO()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Mode Selector");
    }

    public static function getGameSettingsBlazeRod(): Item
    {
        return VanillaItems::BLAZE_ROD()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Settings");
    }

    public static function getManualStart(): Item
    {
        return CustomItemRegistry::PLAY()->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . "Start Game");
    }
}