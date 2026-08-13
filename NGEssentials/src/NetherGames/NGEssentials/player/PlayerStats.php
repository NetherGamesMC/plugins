<?php

namespace NetherGames\NGEssentials\player;

use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use function array_diff;
use function array_map;
use function count;
use function floor;
use function str_repeat;
use function time;

class PlayerStats
{
    public const TYPE_GLOBAL = 0;
    public const TYPE_DAILY = 1;
    public const TYPE_WEEKLY = 2;
    public const TYPE_BIWEEKLY = 3;
    public const TYPE_MONTHLY = 4;
    public const TYPE_YEARLY = 5;

    private const TYPES = [
        self::TYPE_GLOBAL,
        self::TYPE_DAILY,
        self::TYPE_WEEKLY,
        self::TYPE_BIWEEKLY,
        self::TYPE_MONTHLY,
        self::TYPE_YEARLY
    ];

    public static function saveOnlineTime(NGPlayer $player): void
    {
        self::createStats($player->getXuid(), static function () use ($player): void {
            MySQLCredentials::executeChange("player_stats.increase_online_time", ["xuid" => $player->getXuid(), "time" => (int)floor((time() - $player->getJoinTime()) / 60)]);
        });
    }

    public static function getOnlineTime(Player $player, int $type, callable $callable): void
    {
        Utils::validateCallableSignature(function (int $time): void {}, $callable);

        MySQLCredentials::executeSelect("player_stats.get_online_time", ["xuid" => $player->getXuid(), "type" => $type], static function (array $rows) use ($callable): void {
            $callable($rows[0]["online_time"] ?? 0);
        });
    }

    public static function createStats(string $xuid, callable $callable): void
    {
        Utils::validateCallableSignature(function (): void {}, $callable);

        MySQLCredentials::executeSelect("player_stats.get_types", ["xuid" => $xuid], static function (array $rows) use ($xuid, $callable): void {
            $types = array_diff(self::TYPES, array_map(static function (array $row): int {
                return $row["type"];
            }, $rows));

            if (count($types) > 0) {
                $data = [];

                foreach ($types as $type) {
                    $data[] = $xuid;
                    $data[] = $type;
                }

                MySQLCredentials::executeInsertRaw('INSERT IGNORE INTO player_stats (xuid, type) VALUES ' . str_repeat('(?, ?), ', count($types) - 1) . '(?, ?)', $data, $callable);
            } else {
                $callable();
            }
        });
    }
}