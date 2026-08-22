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

use pocketmine\utils\TextFormat;
use function array_rand;
use function explode;
use function floor;
use function in_array;
use function max;
use function round;
use function rtrim;
use function str_pad;
use function str_split;
use function strlen;
use function substr_count;
use function trim;
use function usort;

class TextUtils
{
    public const lineLength = 30;
    public const charWidth = 6;
    public const spaceChar = ' ';
    public const charWidths = [
        ' ' => 4,
        '!' => 2,
        '"' => 5,
        '\'' => 3,
        '(' => 5,
        ')' => 5,
        '*' => 5,
        ',' => 2,
        '.' => 2,
        ':' => 2,
        ';' => 2,
        '<' => 5,
        '>' => 5,
        '@' => 7,
        'I' => 4,
        '[' => 4,
        ']' => 4,
        'f' => 5,
        'i' => 2,
        'k' => 5,
        'l' => 3,
        't' => 4,
        '' => 5,
        '|' => 2,
        '~' => 7,
        '█' => 9,
        '░' => 8,
        '▒' => 9,
        '▓' => 9,
        '▌' => 5,
        '─' => 9
        //'-' => 9,
    ];

    public const SWITCH_COLOR = '~';

    public static function centerLine(string $input): string
    {
        return self::center($input, self::lineLength * self::charWidth);
    }

    public static function center(string $input, int $maxLength = 0, bool $addRightPadding = false): string
    {
        $lines = explode("\n", trim($input));

        $sortedLines = $lines;
        usort($sortedLines, static function (string $a, string $b) {
            return self::getPixelLength($b) <=> self::getPixelLength($a);
        });

        $longest = $sortedLines[0];

        if ($maxLength === 0) {
            $maxLength = self::getPixelLength($longest);
        }

        $result = '';

        $spaceWidth = self::getCharWidth(self::spaceChar);

        foreach ($lines as $sortedLine) {
            $len = max($maxLength - self::getPixelLength($sortedLine), 0);
            $padding = (int)round($len / (2 * $spaceWidth));
            $paddingRight = (int)floor($len / (2 * $spaceWidth));
            $result .= str_pad(self::spaceChar, $padding) . $sortedLine . ($addRightPadding ? str_pad(self::spaceChar, $paddingRight) : '') . "\n";
        }

        $result = rtrim($result, "\n");

        return $result;
    }

    public static function getPixelLength(string $line): int
    {
        $length = 0;
        foreach (str_split(TextFormat::clean($line)) as $c) {
            $length += self::getCharWidth($c);
        }

        // +1 for each bold character
        $length += substr_count($line, TextFormat::BOLD);
        return $length;
    }

    private static function getCharWidth(string $c): int
    {
        return self::charWidths[$c] ?? self::charWidth;
    }

    public static function getRandomColor(): string
    {
        return self::getColors()[array_rand(self::getColors())];
    }

    /**
     * @return string[]
     */
    public static function getColors(): array
    {
        return [
            TextFormat::DARK_BLUE,
            TextFormat::DARK_GREEN,
            TextFormat::DARK_AQUA,
            TextFormat::DARK_RED,
            TextFormat::DARK_PURPLE,
            TextFormat::GOLD,
            TextFormat::BLUE,
            TextFormat::GREEN,
            TextFormat::AQUA,
            TextFormat::RED,
            TextFormat::LIGHT_PURPLE,
            TextFormat::YELLOW,
            TextFormat::WHITE,
        ];
    }

    public static function addOrdinalNumberSuffix(int $number): string
    {
        if (in_array(($number % 100), [11, 12, 13], true)) {
            return $number . 'th';
        }

        return match ($number % 10) {
            1 => $number . 'st',
            2 => $number . 'nd',
            3 => $number . 'rd',
            default => $number . 'th',
        };
    }
}