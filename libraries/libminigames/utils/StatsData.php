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

use libminigames\events\player\PlayerStatChangeEvent;
use pocketmine\player\Player;
use RuntimeException;
use function array_map;
use function str_replace;
use function strtolower;

abstract class StatsData
{
    public const KILLS = 0;
    public const DEATHS = 1;
    public const WINS = 2;
    public const LOSSES = 3;
    public const PERFECT_SHOTS = 4;

    /** @var array<string, array<int, int>> */
    private array $stats = [];
    /** @var array<string, array<int, int>> */
    private array $tempStats = [];
    /** @var array<string, array<string, array<int, int>>> */
    private array $kills = [];
    /** @var array<int, string|null> */
    private array $statTypes = [];

    /**
     * @param string $mode
     * @param array<string> $types
     */
    public function __construct(private string $mode, private array $types = [])
    {
        $this->registerStat(self::KILLS, 'kills');
        $this->registerStat(self::DEATHS, 'deaths');
        $this->registerStat(self::WINS, 'wins');
        $this->registerStat(self::LOSSES, 'losses');
        $this->registerStat(self::PERFECT_SHOTS, null);

        $this->types = array_map("strtolower", $types);
        $this->mode = strtolower($mode);
    }

    protected function registerStat(int $id, ?string $name): void
    {
        if (isset($this->statTypes[$id])) {
            throw new RuntimeException('Stat with id ' . $id . ' is already registered');
        }

        $this->statTypes[$id] = $name;
    }

    public function addKill(Player|string $player, Player|string $victim, int $id): void
    {
        $playerXuid = $player instanceof Player ? $player->getXuid() : $player;
        $victimXuid = $victim instanceof Player ? $victim->getXuid() : $victim;

        $this->kills[$playerXuid][$victimXuid][$id] = ($this->kills[$playerXuid][$victimXuid][$id] ?? 0) + 1;

        $this->addValue($player, $id);
    }

    public function addValue(Player|string $player, int $id, int $value = 1): void
    {
        $playerXuid = $player instanceof Player ? $player->getXuid() : $player;
        if (isset($this->statTypes[$id])) {
            $oldValue = $this->getValue($player, $id);
            $newValue = $oldValue + $value;

            $this->stats[$playerXuid][$id] = $newValue;
            $this->tempStats[$playerXuid][$id] = $this->getValue($player, $id, true) + $value;

            if ($player instanceof Player) {
                (new PlayerStatChangeEvent($player, $id, $this->getStatName($id), $oldValue, $newValue))->call();
            }
        } else {
            throw new RuntimeException('Stat with id ' . $id . " doesn't exist");
        }
    }

    /**
     * The map of player xuid to victim xuid to stat id to count, tracking every kill that happened
     * in this stats instance.
     *
     * @return array<string, array<string, array<int, int>>>
     */
    public function getKills(): array
    {
        return $this->kills;
    }

    /**
     * Returns the map of stat id to value (non-zero) for the given player, keyed by the ids
     * registered via {@see StatsData::registerStat()}.
     *
     * @param Player $player
     * @return array<int, int>
     */
    public function getValues(Player $player): array
    {
        $result = [];

        foreach ($this->statTypes as $id => $name) {
            $value = $this->getValue($player, $id);
            if ($value !== 0) {
                $result[$id] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns a map of stat name to value for the given player.
     *
     * @param Player $player
     * @return array<string, int>
     */
    public function snapshot(Player $player): array
    {
        $result = [];

        foreach ($this->statTypes as $id => $name) {
            if ($name === null) {
                continue;
            }

            $value = $this->getValue($player, $id);
            if ($value !== 0) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    private function getStatName(int $id): string
    {
        return $this->statTypes[$id] ?? "stat_$id";
    }

    /**
     * The mode identifier this stats data was constructed with.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return array<string> The registered stat type/variation names.
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Returns the resolvable database column name for the given stat id.
     *
     * <p>The <code>*mode*</code> and <code>*type*</code> placeholders used by subclasses are
     * substituted here. Consumers (such as an external persistence module) should only touch
     * stats whose {@see StatsData::getColumnName()} resolves to a non-null name.
     *
     * @param int $id
     * @param int $typeId
     */
    public function getColumnName(int $id, int $typeId = -1): string
    {
        $columnName = $this->statTypes[$id] ?? '';

        if ($columnName === '') {
            return '';
        }

        $columnName = str_replace('*mode*', $this->mode, $columnName);

        if ($typeId !== -1) {
            $columnName = str_replace('*type*', $this->types[$typeId] ?? 'unknown', $columnName);
        }

        return $columnName;
    }

    /**
     * Returns whether the given stat id should be persisted (has a resolvable column name).
     *
     * @param int $id
     */
    public function isSaveableStat(int $id): bool
    {
        return ($this->statTypes[$id] ?? null) !== null;
    }

    public function resetTempStats(Player $player): void
    {
        unset($this->tempStats[$player->getXuid()]);
    }

    public function getValue(Player|string $player, int $id, bool $temp = false): int
    {
        $playerXuid = $player instanceof Player ? $player->getXuid() : $player;
        if ($temp) {
            return $this->tempStats[$playerXuid][$id] ?? 0;
        }
        return $this->stats[$playerXuid][$id] ?? 0;
    }
}
