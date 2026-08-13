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

use libasynCurl\Curl;
use libminigames\Arena;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerStats;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use RuntimeException;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_shift;
use function array_slice;
use function arsort;
use function count;
use function explode;
use function getenv;
use function implode;
use function in_array;
use function str_replace;
use function strtolower;

abstract class StatsData
{
    public const KILLS = 0;
    public const DEATHS = 1;
    public const WINS = 2;
    public const LOSSES = 3;
    public const PERFECT_SHOTS = 4;

    private const DB_COLUMN = 0;
    private const DB_VALUE = 1;

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
        $ess = NGEssentials::getInstance();
        if ($ess->getServerManager()->getServerType() === ServerManager::SETUP) {
            return;
        }

        $playerXuid = $player instanceof Player ? $player->getXuid() : $player;
        if (isset($this->statTypes[$id])) {
            $this->stats[$playerXuid][$id] = $this->getValue($player, $id) + $value;
            $this->tempStats[$playerXuid][$id] = $this->getValue($player, $id, true) + $value;
        } else {
            throw new RuntimeException('Stat with id ' . $id . " doesn't exist");
        }
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

    private function saveTournament(Arena $arena, int $typeId): void
    {
        $tournamentManager = NGEssentials::getInstance()->getTournamentManager();
        if (!$tournamentManager->inTournament()) {
            return;
        }

        foreach ($arena->getXuids() as $xuid) {
            if (isset($this->kills[$xuid])) {
                foreach ($this->kills[$xuid] as $victimXuid => $kills) {
                    foreach ($kills as $id => $value) {
                        $columnName = $this->getColumnName($id, $typeId);

                        if (in_array($columnName, $tournamentManager->getColumns())) {
                            MySQLCredentials::executeInsertRaw('INSERT INTO tournament_logs (type, origin_xuid, target_xuid) VALUES (?, ?, ?)', [$columnName, $xuid, $victimXuid]);
                        }
                    }
                }
            }
        }
    }

    public function sendLeaderboard(Arena $arena, int $id, string $title): void
    {
        /** @var array<string, int> $stats */
        $stats = array_reduce(array_keys($xuids = $arena->getXuids()), function (array $carry, string $playerName) use ($id, $xuids): array {
            $value = $this->getValue($xuids[$playerName], $id);
            if ($value !== 0) {
                $carry[$playerName] = $value;
            }
            return $carry;
        }, []);

        if (count($stats) === 0) {
            return;
        }

        arsort($stats);

        $arena->broadcastMessage('§e§l----------------------------', true);
        $arena->broadcastMessage($title, true);

        $podium = array_slice($stats, 0, 3, true);
        $place = 0;
        foreach ($podium as $playerName => $value) {
            $place++;

            $icon = match ($place) {
                1 => CustomIcon::TROPHY_GOLD,
                2 => CustomIcon::TROPHY_SILVER,
                3 => CustomIcon::TROPHY_BRONZE,
                default => throw new RuntimeException('Invalid place ' . $place)
            };

            $arena->broadcastMessage($icon . '§r§7- §b' . $playerName . ' - §e' . $value, true);
        }

        $arena->broadcastMessage('§e§l----------------------------', true);
    }

    public function save(Arena $arena): void
    {
        $typeId = $arena instanceof TypeArena ? $arena->getType() : -1;

        if (count($this->stats) === 0) {
            return;
        }

        /** @var array<string, array{key: string, value: int}> $playerStats */
        $playerStats = [];
        foreach (\pocketmine\utils\Utils::stringifyKeys($this->stats) as $xuid => $stats) {
            $queryData = [];
            $playerStats[$xuid] = [];
            $savedKeys = [];

            foreach ($stats as $id => $value) {
                if (!$this->isSaveableStat($id)) {
                    continue;
                }

                $columnName = $this->getColumnName($id, $typeId);
                $queryData[self::DB_VALUE][] = $value;
                $queryData[self::DB_COLUMN][] = $columnName . ' = ' . $columnName . ' + ?';

                if (($key = $this->getKey($id)) !== null && !in_array($key, $savedKeys)) {
                    $playerStats[$xuid][] = [
                        "key" => $key,
                        "value" => $value
                    ];
                    $savedKeys[] = $key;
                }
            }

            $queryData[self::DB_VALUE][] = $xuid;

            PlayerStats::createStats($xuid, static function () use ($queryData): void {
                /* @phpstan-ignore-next-line */
                MySQLCredentials::executeInsertRaw('UPDATE player_stats SET ' . implode(', ', $queryData[self::DB_COLUMN]) . ' WHERE xuid = ?;', $queryData[self::DB_VALUE]);
            });
        }

        if (($api = getenv("CATALYST_URL")) !== false) {
            Curl::postRequest($api . '/v1/ingest', [
                "server_type" => strtolower($arena->getPlugin()->getMinigameTag()),
                "game_id" => $arena->getReplayId(),
                "game_type" => $this->mode,
                "game_variation" => $this->types[$typeId] ?? '',
                "time" => date('Y-m-d H:i:s', $arena->getStartTime()),
                "players" => array_map(function (string $xuid, array $stats): array {
                    return [
                        "xuid" => $xuid,
                        "stats" => $stats
                    ];
                }, array_keys($playerStats), $playerStats)
            ]);
        }

        $this->saveTournament($arena, $typeId);
    }

    /**
     * @pre $this->statsTypes[$id] !== null
     */
    private function getKey(int $id): ?string
    {
        /** @var string $columnName */
        $columnName = $this->statTypes[$id];

        $parts = explode('_', $columnName);
        array_shift($parts); // remove the server type

        if (count($parts) === 0) {
            return null;
        }

        while (in_array($parts[0] ?? '', ['*type*', '*mode*'])) {
            array_shift($parts);
        }

        return implode('_', $parts);
    }

    /**
     * @pre $this->statsTypes[$id] exists
     */
    private function isSaveableStat(int $id): bool
    {
        return $this->statTypes[$id] !== null;
    }

    /**
     * @pre $this->statsTypes[$id] !== null
     */
    private function getColumnName(int $id, int $typeId = -1): string
    {
        /** @var string $columnName */
        $columnName = $this->statTypes[$id];

        $columnName = str_replace('*mode*', $this->mode, $columnName);

        if ($typeId !== -1) {
            $columnName = str_replace('*type*', $this->types[$typeId] ?? 'unknown', $columnName);
        }

        return $columnName;
    }
}