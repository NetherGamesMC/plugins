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

use pocketmine\utils\TextFormat;
use function array_rand;
use function explode;

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

    public const DEBUG_MODE = 0;
    public const WARNING_MODE = 1;
    public const EMERGENCY_MODE = 2;
    public const NOTICE_MODE = 3;
    public const INFO_MODE = 4;


    public static function formatMessage(int $mode, string $message): string
    {
        switch ($mode) {
            case self::DEBUG_MODE:
                $primary = TextFormat::DARK_GRAY;
                $secondary = TextFormat::GRAY;
                $modeLogo = CustomIcon::DEBUG;
                break;
            case self::WARNING_MODE:
                $primary = TextFormat::YELLOW;
                $secondary = TextFormat::GOLD;
                $modeLogo = CustomIcon::WARNING;
                break;
            case self::EMERGENCY_MODE:
                $primary = TextFormat::RED;
                $secondary = TextFormat::YELLOW;
                $modeLogo = CustomIcon::EMERGENCY;
                break;
            case self::NOTICE_MODE:
                $primary = TextFormat::GREEN;
                $secondary = TextFormat::WHITE;
                $modeLogo = CustomIcon::NOTICE;
                break;
            default:
                $primary = TextFormat::DARK_AQUA;
                $secondary = TextFormat::AQUA;
                $modeLogo = CustomIcon::INFO;
                break;
        }

        $buffer = $modeLogo;

        foreach (explode(self::SWITCH_COLOR, $message) as $key => $seperated) {
            $buffer .= $key % 2 === 0 ? $primary : $secondary . $seperated;
        }

        return $buffer;
    }

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
            //$result .= str_pad(self::spaceChar, $padding) . $sortedLine . TextFormat::RESET . ($addRightPadding ? str_pad(self::spaceChar, $paddingRight) : "") . "\n";
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