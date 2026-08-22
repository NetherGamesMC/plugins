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
 * @author driesboy
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\minigames\listener;

use libasynCurl\Curl;
use libminigames\Arena;
use libminigames\events\arena\ArenaEndEvent;
use libminigames\events\player\PlayerQuitEvent;
use libminigames\utils\TypeArena;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerStats;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use function array_keys;
use function array_map;
use function count;
use function date;
use function getenv;
use function implode;
use function in_array;
use function strtolower;

/**
 * Persists match statistics to the player_stats database and ingests them into Catalyst.
 *
 * <p>Statistics are written immediately when a player leaves mid-match (so partial stats are never
 * lost), and once more for the players still present when the arena ends. Tournament kill logs are
 * written while a tournament is active.
 */
final class PersistenceListener implements Listener
{
    private const DB_VALUE = 0;
    private const DB_COLUMN = 1;

    /** @var array<int, array<string, array<string, int>>> arenaId => xuid => statName => value */
    private array $pendingIngest = [];

    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $arena = $event->getArena();
        if (!$arena->isRunning() && !$arena->isFinishing()) {
            return;
        }
        if ($arena->isPartyGame()) {
            return;
        }
        if ($event->getReason() === PlayerQuitEvent::DISCONNECT_KICK) {
            return;
        }

        $player = $event->getPlayer();
        if (!in_array($player, $arena->getPlayers(false), true)) {
            return;
        }

        $typeId = $arena instanceof TypeArena ? $arena->getType() : -1;
        $this->writePlayerStats($arena, $player, $typeId);

        $stats = $arena->getStatsData()->snapshot($player);
        if (count($stats) > 0) {
            $this->pendingIngest[$arena->getId()][$player->getXuid()] = $stats;
        }
    }

    /**
     * @param ArenaEndEvent $event
     *
     * @priority MONITOR
     */
    public function onEnd(ArenaEndEvent $event): void
    {
        $arena = $event->getArena();
        $statsData = $arena->getStatsData();
        $typeId = $arena instanceof TypeArena ? $arena->getType() : -1;

        $playerStats = $this->pendingIngest[$arena->getId()] ?? [];

        foreach ($event->getMatchResult()->getPlayerStats() as $playerMatchStats) {
            $player = $playerMatchStats->getPlayer();
            $values = $statsData->getValues($player);
            if (count($values) === 0) {
                continue;
            }

            $this->writePlayerStats($arena, $player, $typeId);
            $playerStats[$player->getXuid()] = $playerMatchStats->getStats();
        }

        unset($this->pendingIngest[$arena->getId()]);

        $this->saveTournament($arena);

        if (($api = getenv("CATALYST_URL")) !== false && count($playerStats) > 0) {
            $gameType = $statsData->getMode();
            $variation = $statsData->getTypes()[$typeId] ?? '';

            Curl::postRequest($api . '/v1/ingest', [
                "server_type" => strtolower($arena->getPlugin()->getMinigameTag()),
                "game_id" => ReplayListener::getReplayId($arena),
                "game_type" => $gameType,
                "game_variation" => $variation,
                "time" => date('Y-m-d H:i:s', $arena->getStartTime()),
                "players" => array_map(static function (string $xuid, array $stats): array {
                    return [
                        "xuid" => $xuid,
                        "stats" => $stats
                    ];
                }, array_keys($playerStats), $playerStats)
            ]);
        }
    }

    /**
     * Persists a single player's in-memory stats into player_stats using the resolvable columns.
     */
    private function writePlayerStats(Arena $arena, Player $player, int $typeId): void
    {
        $statsData = $arena->getStatsData();
        $values = $statsData->getValues($player);
        if (count($values) === 0) {
            return;
        }

        $queryData = [];
        $columns = [];
        foreach ($values as $id => $value) {
            $column = $statsData->getColumnName($id, $typeId);
            if ($column === '' || !$statsData->isSaveableStat($id) || in_array($column, $columns, true)) {
                continue;
            }

            $columns[] = $column;
            $queryData[self::DB_VALUE][] = $value;
            $queryData[self::DB_COLUMN][] = $column . ' = ' . $column . ' + ?';
        }

        if (count($queryData[self::DB_COLUMN]) === 0) {
            return;
        }

        $values = $queryData[self::DB_VALUE];
        $values[] = $player->getXuid();
        $columnsSql = implode(', ', $queryData[self::DB_COLUMN]);

        PlayerStats::createStats($player->getXuid(), static function () use ($columnsSql, $values): void {
            MySQLCredentials::executeInsertRaw('UPDATE player_stats SET ' . $columnsSql . ' WHERE xuid = ?;', $values);
        });
    }

    /**
     * Writes per-kill rows into tournament_logs while a tournament is active.
     */
    private function saveTournament(Arena $arena): void
    {
        $tournamentManager = $this->plugin->getTournamentManager();
        if (!$tournamentManager->inTournament()) {
            return;
        }

        $kills = $arena->getStatsData()->getKills();
        $typeId = $arena instanceof TypeArena ? $arena->getType() : -1;

        foreach ($kills as $originXuid => $victims) {
            foreach ($victims as $targetXuid => $stats) {
                foreach ($stats as $id => $count) {
                    $columnName = $arena->getStatsData()->getColumnName($id, $typeId);
                    if ($columnName === '' || !in_array($columnName, $tournamentManager->getColumns(), true)) {
                        continue;
                    }

                    MySQLCredentials::executeInsertRaw('INSERT INTO tournament_logs (type, origin_xuid, target_xuid) VALUES (?, ?, ?)', [$columnName, $originXuid, $targetXuid]);
                }
            }
        }
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}