<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils;

use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\item\Item;
use pocketmine\player\Player;

class LobbyItems
{
    public const UTILITY_INDEX = 1;
    public const TELEPORTER_STAFF_INDEX = 3;
    public const TELEPORTER_INDEX = 4;
    public const STAFF_PORTAL_INDEX = 5;
    public const SOCIAL_INDEX = 7;
    public const PROFILE_INDEX = 8;

    public static function setLobbyInventory(Player $player): void
    {
        $staff = Permissions::isStaff($player);
        $slot = $staff ? self::TELEPORTER_STAFF_INDEX : self::TELEPORTER_INDEX;
        $player->getInventory()->setHeldItemIndex($slot);

        $items = [
            self::UTILITY_INDEX => self::getCosmeticItem(),
            self::SOCIAL_INDEX => self::getSocialItem(),
            self::PROFILE_INDEX => self::getProfileSettingsItem(),
            $slot => self::getTeleporterItem(),
        ];

        if ($staff) {
            $items[self::STAFF_PORTAL_INDEX] = self::getStaffPortalItem();
        }

        $player->getInventory()->setContents($items);
    }

    public static function getStaffPortalItem(): Item
    {
        return CustomItemRegistry::STAFF_PORTAL()->setCustomName('§r§d§lStaff Portal');
    }

    public static function getCosmeticItem(): Item
    {
        return CustomItemRegistry::COSMETICS()->setCustomName('§r§b§lCosmetics');
    }

    public static function getTeleporterItem(): Item
    {
        return CustomItemRegistry::TELEPORTER()->setCustomName('§r§a§lTeleporter');
    }

    public static function getSocialItem(): Item
    {
        return CustomItemRegistry::SOCIAL_MENU()->setCustomName('§r§d§lSocial Menu');
    }

    public static function getProfileSettingsItem(): Item
    {
        return CustomItemRegistry::PREFERENCES()->setCustomName('§r§e§lProfile Settings');
    }

    public static function getPauseReplayTorch(): Item
    {
        return CustomItemRegistry::PAUSE()->setCustomName('§r§b§lPause the replay');
    }

    public static function getSpeedReplayFeather(int $amount = 1): Item
    {
        return CustomItemRegistry::SPEED_MODIFIER()->setCustomName('§r§b§l' . $amount . 'x Speed')->setCount($amount);
    }

    public static function getResumeReplayTorch(): Item
    {
        return CustomItemRegistry::PLAY()->setCustomName('§r§b§lResume the replay');
    }

    public static function getNoClipToggleItem(): Item
    {
        return CustomItemRegistry::NO_CLIP()->setCustomName('§r§e§lToggle No Clip');
    }

    public static function getSpectatorBed(): Item
    {
        return CustomItemRegistry::LEAVE()->setCustomName('§r§c§lReturn to Lobby');
    }

    public static function getSpectatorCompass(): Item
    {
        return CustomItemRegistry::TELEPORTER()->setCustomName('§r§a§lTeleporter');
    }
}