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

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\utils\DyeColor;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use function abs;
use function array_map;
use function array_rand;
use function array_unique;
use function array_unshift;
use function array_values;
use function fclose;
use function fmod;
use function min;
use function natcasesort;
use function preg_replace_callback;
use function stream_get_contents;
use function strtolower;

class Utils
{
    public const ROTATION_INDEX_PITCH = 0;
    public const ROTATION_INDEX_YAW = 1;

    /**
     * @param Player[] $players
     *
     * @return string[]
     * @deprecated use PlayerManager->getPlayerNames($players) instead
     */
    public static function getPlayersName(array $players): array
    {
        $names = array_map(static function (Player $player): string {
            return $player->getName();
        }, $players);

        natcasesort($names);

        return array_values($names);
    }

    public static function timeToHR(float $time): string
    {
        $time = ($time < 1) ? 1.0 : $time;
        $tokens = [
            365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            7 * 24 * 60 * 60 => 'week',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second',
        ];
        $return = '';

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) {
                continue;
            }

            $numberOfUnits = floor($time / $unit);
            $time -= $numberOfUnits * $unit;

            $return .= $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's, ' : ', ');
        }

        return substr($return, 0, -2);
    }

    /**
     * @param array $array
     * @param mixed $value
     * @return array
     */
    public static function pushValue(array $array, mixed $value): array
    {
        array_unshift($array, $value);

        return array_values(array_unique($array));
    }

    public static function hasClassicUI(Player $player): bool
    {
        /** @var NGPlayer $player */
        return $player->getUI() === 0;
    }

    public static function isWeekend(?int $time = null): bool
    {
        return (date('N', $time ?? time()) >= 6);
    }

    public static function getRomanNumber(int $number): string
    {
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $result = '';

        foreach ($map as $roman => $int) {
            // Determine the number of matches
            $matches = (int)($number / $int);

            // Add the same number of characters to the string
            $result .= str_repeat($roman, $matches);

            // Set the number to be the remainder of the number and the value
            $number %= $int;
        }

        return $result;
    }

    public static function getResourceContent(string $filename): string
    {
        $ess = NGEssentials::getInstance();

        /** @var resource $resource */
        $resource = $ess->getResource($filename);
        /** @var string $data */
        $data = stream_get_contents($resource);

        fclose($resource);

        return $data;
    }

    public static function pascalCaseToSnakeCase(string $string): string
    {
        $snakeCase = preg_replace_callback('/([A-Z])/', function ($matches) {
            return '_' . strtolower($matches[1]);
        }, $string);

        if (str_starts_with($snakeCase, '_')) {
            return substr($snakeCase, 1);
        }

        return $snakeCase;
    }

    /**
     * @param array $array
     * @param int|null $maxDepth
     * @return array
     */
    public static function flattenArray(array $array, ?int $maxDepth = null): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ($maxDepth === null || $maxDepth > 0)) {
                $result = array_merge($result, self::flattenArray($value, $maxDepth === null ? null : $maxDepth - 1));
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Calculates the correct rotational difference.
     * Pitch delta is easy, but yaw caused some problems, as it's not limited/seamlessly overflows into the new value area
     *
     * @param float $oldPitch
     * @param float $oldYaw
     * @param float $newPitch
     * @param float $newYaw
     * @return int[]|float[]
     */
    public static function rotationDifference(float $oldPitch, float $oldYaw, float $newPitch, float $newYaw): array
    {
        $absolutePitchDelta = abs($oldPitch - $newPitch);
        $absoluteYawDelta = min(
            fmod(360 - $oldYaw + $newYaw, 360), // turning right
            fmod(360 - $newYaw + $oldYaw, 360) // turning left
        );

        if ($absolutePitchDelta < Entity::MOTION_THRESHOLD) {
            $absolutePitchDelta = 0;
        }
        if ($absoluteYawDelta < Entity::MOTION_THRESHOLD) {
            $absoluteYawDelta = 0;
        }

        return [
            self::ROTATION_INDEX_PITCH => $absolutePitchDelta,
            self::ROTATION_INDEX_YAW => $absoluteYawDelta
        ];
    }

    public static function getRandomDyeColor(): DyeColor
    {
        $colors = DyeColor::cases();
        return $colors[array_rand($colors)];
    }
}
