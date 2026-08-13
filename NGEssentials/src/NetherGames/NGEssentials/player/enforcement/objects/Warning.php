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

use function str_shuffle;
use function substr;

class Warning
{
    public const UNIQUE_ID = 0;
    public const REASON = 1;
    public const ISSUE_TIME = 2;
    public const ISSUED_BY = 3;
    public const EXTRA_DATA = 4;

    public const REPLAY_ID = 0;
    public const ANTICHEAT_BAN_NAME = 1;

    public function __construct(
        private string $uniqueId,
        private int    $reasonId,
        private int    $issueTime,
        private string $issuedBy,
        private array  $extraData = []
    )
    {
    }

    public static function load(array $warningData): Warning
    {
        return new Warning($warningData[self::UNIQUE_ID], $warningData[self::REASON], $warningData[self::ISSUE_TIME], $warningData[self::ISSUED_BY], $warningData[self::EXTRA_DATA]);
    }

    public static function generateUniqueId(): string
    {
        return substr(str_shuffle('0123456789abcdefghjkmnopqrstuvwxyz'), 0, 5);
    }

    public function getReplayId(): ?int
    {
        return $this->extraData[self::REPLAY_ID] ?? null;
    }

    public function getAnticheatData(): ?string
    {
        return $this->extraData[self::ANTICHEAT_BAN_NAME] ?? null;
    }

    public function toArray(): array
    {
        return [
            self::UNIQUE_ID => $this->getUniqueId(),
            self::REASON => $this->getReasonId(),
            self::ISSUE_TIME => $this->getIssueTime(),
            self::ISSUED_BY => $this->getIssuedBy(),
            self::EXTRA_DATA => $this->getExtraData()
        ];
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function getReasonId(): int
    {
        return $this->reasonId;
    }

    public function getIssueTime(): int
    {
        return $this->issueTime;
    }

    public function getIssuedBy(): string
    {
        return $this->issuedBy;
    }

    private function getExtraData(): array
    {
        return $this->extraData;
    }
}