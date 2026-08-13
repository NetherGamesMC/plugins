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

namespace NetherGames\NGEssentials\player\enforcement\objects;

use DateInterval;
use DateTime;
use function array_search;
use function array_unshift;
use function is_int;

class WarningCategory
{
    private const POINTS = 0;
    private const WARNINGS = 1;

    public function __construct(
        private int   $points = 0,
        /** @var Warning[] */
        private array $warnings = []
    )
    {
    }

    public static function getPunishmentDuration(int $warningPoints, int $timestamp): int
    {
        if ($warningPoints >= 16) {
            return -1;
        }

        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);

        if ($warningPoints >= 14) {
            $dateTime->add(new DateInterval('P8M'));
        } elseif ($warningPoints >= 12) {
            $dateTime->add(new DateInterval('P4M'));
        } elseif ($warningPoints >= 10) {
            $dateTime->add(new DateInterval('P2M'));
        } elseif ($warningPoints >= 8) {
            $dateTime->add(new DateInterval('P1M'));
        } elseif ($warningPoints >= 6) {
            $dateTime->add(new DateInterval('P2W'));
        } elseif ($warningPoints >= 4) {
            $dateTime->add(new DateInterval('P1W'));
        } else {
            $dateTime->add(new DateInterval('P1D'));
        }

        return $dateTime->getTimestamp();
    }

    public static function load(array $value): self
    {
        $warnings = [];

        foreach ($value[self::WARNINGS] as $encodedWarning) {
            $warnings[] = Warning::load($encodedWarning);
        }

        return new WarningCategory($value[self::POINTS], $warnings);
    }

    public function reducePoints(): void
    {
        $this->points--;
    }

    public function getMostRecentWarning(): ?Warning
    {
        return $this->getWarnings()[0] ?? null;
    }

    /**
     * @return Warning[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function removeWarning(Warning $warning, int $points): void
    {
        $index = array_search($warning, $this->getWarnings(), true);

        if (is_int($index)) {
            unset($this->warnings[$index]);
            $this->points -= $points;
        }
    }

    public function toArray(): array
    {
        $encodedWarnings = [];

        foreach ($this->getWarnings() as $warning) {
            $encodedWarnings[] = $warning->toArray();
        }

        return [
            self::POINTS => $this->getPoints(),
            self::WARNINGS => $encodedWarnings
        ];
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function addWarning(Warning $warning, int $points): void
    {
        $warnings = $this->getWarnings();
        array_unshift($warnings, $warning);
        $this->warnings = $warnings;

        $this->addPoints($points);
    }

    public function addPoints(int $points): void
    {
        $this->points += $points;
    }

    public function isEmpty(): bool
    {
        return count($this->getWarnings()) === 0;
    }
}