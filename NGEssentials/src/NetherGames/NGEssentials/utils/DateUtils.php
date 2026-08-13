<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\utils;

use function floor;
use function rtrim;
use function time;

final class DateUtils
{
    public static function formatDiff(int $a, ?int $b = null): string
    {
        $diff = $a - ($b ?? time());

        $days = floor($diff / (60 * 60 * 24));
        $hours = floor(($diff - ($days * 60 * 60 * 24)) / (60 * 60));
        $minutes = floor(($diff - ($days * 60 * 60 * 24) - ($hours * 60 * 60)) / 60);

        $string = '';

        if ($days > 0) {
            $string .= $days . 'days, ';
        }

        if ($hours > 0) {
            $string .= $hours . 'hours, ';
        }

        if ($minutes > 0) {
            $string .= $minutes . 'minutes';
        }

        return rtrim($string, ', ');
    }
}
