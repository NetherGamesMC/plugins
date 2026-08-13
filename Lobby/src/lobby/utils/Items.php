<?php

namespace lobby\utils;

use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\utils\LobbyItems;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\item\FireworkRocketType;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Items
{
    public const ELYTRA_FIREWORK_INDEX = 0;
    public const CHANGELOG_STAFF_INDEX = 4;
    public const TELEPORTER_CHANGELOG_INDEX = 3;
    public const CHANGELOG_INDEX = 5;

    public static function setLobbyInventory(Player $player): void
    {
        LobbyItems::setLobbyInventory($player);

        $inventory = $player->getInventory();
        $inventory->setItem(self::ELYTRA_FIREWORK_INDEX, self::getZoneTeleporter());
    }

    public static function getZoneTeleporter(): Item
    {
        return CustomItemRegistry::ZONES()->setCustomName("§r§5§lZones");
    }

    public static function getElytraFirework(): Item
    {
        return VanillaItems::FIREWORK_ROCKET()->setExplosions([
            new FireworkRocketExplosion(FireworkRocketType::SMALL_BALL, [DyeColor::WHITE], [], false, false)
        ]);
    }

    public static function setParkourInventory(Player $player): void
    {
        $player->getInventory()->setContents([
            0 => self::getParkourLastCheckpoint(),
            4 => self::getParkourRestart(),
            8 => self::getParkourLeave()
        ]);
    }

    public static function setMazeInventory(Player $player): void
    {
        $player->getInventory()->setContents([
            3 => self::getMazeRestart(),
            5 => self::getMazeLeave()
        ]);
    }

    public static function getParkourLeave(): Item
    {
        return CustomItemRegistry::LEAVE()->setCustomName('§r§c§lQuit the parkour');
    }

    public static function getParkourRestart(): Item
    {
        return CustomItemRegistry::RESTART()->setCustomName('§r§c§lRestart the parkour');
    }

    public static function getParkourLastCheckpoint(): Item
    {
        return CustomItemRegistry::LAST_CHECKPOINT()->setCustomName('§r§a§lTeleport to Last Checkpoint');
    }

    public static function getMazeLeave(): Item
    {
        return CustomItemRegistry::LEAVE()->setCustomName('§r§c§lQuit the maze');
    }

    public static function getMazeRestart(): Item
    {
        return CustomItemRegistry::RESTART()->setCustomName('§r§c§lRestart the maze');
    }
}