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

use pocketmine\block\utils\DyeColor;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function str_replace;
use function strtolower;
use const PHP_INT_MAX;

class Utils
{
    public static function sendCompassPosition(Player $player, Vector3 $vector3): void
    {
        $pk = SetSpawnPositionPacket::worldSpawn(BlockPosition::fromVector3($vector3), DimensionIds::OVERWORLD);
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function getTextColorByDyeColor(DyeColor $color): string
    {
        return match ($color) {
            DyeColor::WHITE => TextFormat::WHITE,
            DyeColor::CYAN => TextFormat::DARK_AQUA,
            DyeColor::YELLOW => TextFormat::YELLOW,
            DyeColor::LIME => TextFormat::GREEN,
            DyeColor::BLUE => TextFormat::DARK_BLUE,
            DyeColor::RED => TextFormat::RED,
            DyeColor::GRAY => TextFormat::DARK_GRAY,
            DyeColor::PINK => TextFormat::LIGHT_PURPLE,
            DyeColor::ORANGE => TextFormat::GOLD,
            DyeColor::LIGHT_GRAY => TextFormat::GRAY,
            DyeColor::LIGHT_BLUE => TextFormat::AQUA,
            DyeColor::PURPLE => TextFormat::DARK_PURPLE,
            default => TextFormat::WHITE,
        };
    }

    public static function convertDamageToWoolName(DyeColor $color): string
    {
        if ($color === DyeColor::LIGHT_GRAY) {
            return 'wool_colored_silver.png';
        }

        return 'wool_colored_' . strtolower(str_replace(' ', '_', $color->getDisplayName())) . '.png';
    }

    /**
     * @param array<string> $values
     * @return string
     */
    public static function getPrettyList(array $values): string
    {
        $last = array_pop($values);
        $output = implode(', ', $values);
        if (strlen($output) > 0) {
            $output .= ' and ';
        }
        $output .= $last;
        return $output;
    }


    /**
     * @param Player $player
     * @param Player[] $players
     * @return Player|null
     */
    public static function getNearestPlayer(Player $player, array $players): ?Player
    {
        $result = null;
        $lastDistance = PHP_INT_MAX;

        foreach ($players as $p) {
            $distance = $player->getLocation()->distance($p->getLocation());
            if ($distance < $lastDistance) {
                $lastDistance = $distance;
                $result = $p;
            }
        }

        return $result;
    }
}