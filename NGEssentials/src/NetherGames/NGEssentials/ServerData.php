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

namespace NetherGames\NGEssentials;

use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\scoreboard\Scoreboard;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\utils\TextFormat;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function strtoupper;

class ServerData extends BaseClass
{
    private const DATA_TYPES = [
        self::PROXY => self::BOOL,
        self::ANNOUNCEMENTS => self::ARRAY,
        self::TITLES => self::ARRAY,
        self::BOARDS => self::ARRAY,
        self::NPCS => self::ARRAY,
        self::BOSSBAR => self::STRING,
        self::STREAM => self::BOOL,
        self::TOURNAMENT => self::ARRAY,
    ];

    //DATA TYPES
    public const ARRAY = 0;
    public const BOOL = 1;
    //public const FLOAT = 2;
    //public const INT = 3;
    //public const OBJECT = 4;
    public const STRING = 5;

    public const API = 1;
    public const PROXY = 2;
    public const ANNOUNCEMENTS = 3;
    public const TITLES = 4;
    public const BOSSBAR = 5;
    public const SCOREBOARD = 6;
    public const BOARDS = 7;
    public const NPCS = 8;
    public const TOURNAMENT = 9;
    // Reserved 10
    public const STREAM = 11;

    /** @var array */
    private array $serverData = [];

    public function getFloat(int $id): float
    {
        return (float)$this->getValue($id);
    }

    /**
     * @param int $id
     * @return mixed
     */
    private function getValue(int $id): mixed
    {
        if (!isset($this->serverData[$id])) {
            $this->serverData[$id] = $this->getDefaultValue($id);
        }

        return $this->serverData[$id];
    }

    /**
     * @param int $id
     * @return array|bool|Scoreboard|null
     */
    public function getDefaultValue(int $id): Scoreboard|bool|array|null|string
    {
        switch ($id) {
            case self::SCOREBOARD:
                return new Scoreboard(TextFormat::GOLD . TextFormat::BOLD . strtoupper(ServerManager::getName($this->getPlugin()->getServerManager()->getServerType())), SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, SetDisplayObjectivePacket::SORT_ORDER_DESCENDING);
            case self::STREAM:
                return false;
        }

        $dataType = self::DATA_TYPES[$id];
        return match ($dataType) {
            self::ARRAY => [],
            self::BOOL => true,
            self::STRING => "",
        };
    }

    public function getString(int $id): string
    {
        return (string)$this->getValue($id);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function getBool(int $id): bool
    {
        return (bool)$this->getValue($id);
    }

    public function addInt(int $id, int $addon = 1): int
    {
        $int = $this->getInt($id) + $addon;

        $this->setValue($id, $int);

        return $int;
    }

    public function getInt(int $id): int
    {
        return (int)$this->getValue($id);
    }

    /**
     * @param int $id
     * @param mixed $value
     */
    public function setValue(int $id, mixed $value): void
    {
        if (($validatedValue = $this->validateValue($id, $value)) === null) {
            $this->getPlugin()->getLogger()->alert('Invalid datatype for id: ' . $id . '| value: ' . (string)$value);
        } else {
            $this->serverData[$id] = $validatedValue;
        }
    }

    /**
     * @param int $id
     * @param float|object|int|bool|array|string|null $value
     * @return bool|int|null|array|object|string|float
     */
    public function validateValue(int $id, float|object|int|bool|array|string|null $value): float|object|int|bool|array|string|null
    {
        $data_type = self::DATA_TYPES[$id];

        if ($data_type === self::ARRAY && is_array($value)) {
            return $value;
        }

        if ($data_type === self::BOOL) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value) || is_int($value) || is_float($value)) {
                return (bool)$value;
            }
        }

        /*if ($data_type === self::FLOAT && (is_float($value) || is_int($value))) {
            return $value;
        }*/

        /*if ($data_type === self::INT) {
            if (is_int($value)) {
                return $value;
            }

            if (is_float($value) || is_bool($value)) {
                return (int)$value;
            }
        }*/

        /*if ($data_type === self::OBJECT && is_object($value)) {
            return $value;
        }*/

        if ($data_type === self::STRING && is_string($value)) {
            return $value;
        }

        return null;
    }

    public function unsetValue(int $id): void
    {
        unset($this->serverData[$id]);
    }

    public function getArray(int $id): array
    {
        return (array)$this->getValue($id);
    }

    public function getScoreBoard(): Scoreboard
    {
        return $this->getValue(self::SCOREBOARD);
    }
}