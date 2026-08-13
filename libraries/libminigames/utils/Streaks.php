<?php
declare(strict_types=1);

namespace libminigames\utils;

use libminigames\utils\streaks\Streak;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\MySQLCredentials;

class Streaks
{
    public const TYPE_WIN = "win";

    public static function Increment(string $xuid, string $gameKey, ?callable $onSelected, ?callable $onError): void
    {
        MySQLCredentials::executeSelect(queryName: "streaks.increment", args: [
            "xuid" => $xuid,
            "gameKey" => $gameKey,
        ], onSelect: function (array $rows) use ($xuid, $gameKey, $onSelected) {
            if (!$onSelected) {
                return;
            }
            if (count($rows) != 1) {
                NGEssentials::getInstance()->getLogger()->warning("Requested increment of the statistics of $xuid for $gameKey, but the request returned " . count($rows) . " results");
                return;
            }
            $onSelected(Streak::FromSQL($rows[0]));
        }, onError: $onError);
    }

    public static function Reset(string $xuid, string $gameKey, ?callable $onUpdated, ?callable $onError): void
    {
        MySQLCredentials::executeChange("streaks.reset", [
            "xuid" => $xuid,
            "gameKey" => $gameKey,
        ], $onUpdated, $onError);
    }

    /**
     * @param string $xuid
     * @param callable|null $onReceive This function will receive an array of Streak objects, might be empty, but never null. The function will not be called if the query errors
     * @param callable|null $onError
     * @return void
     */
    public static function GetAll(string $xuid, ?callable $onReceive, ?callable $onError): void
    {
        MySQLCredentials::executeSelect("streaks.get_all", [
            "xuid" => $xuid,
        ], onSelect: function (array $rows) use ($onReceive) {
            if (!$onReceive) {
                return;
            }
            $results = [];

            foreach ($rows as $row) {
                $results[] = Streak::FromSQL($row);
            }

            $onReceive($results);
        }, onError: $onError);
    }

    public static function GetSingle(string $xuid, string $gameKey, ?callable $onReceive, ?callable $onError): void
    {
        MySQLCredentials::executeSelect(
            queryName: "streaks.get_single",
            args: ["xuid" => $xuid, "gameKey" => $gameKey],
            onSelect: function (array $rows) use ($xuid, $gameKey, $onReceive) {
                if (!$onReceive) {
                    return;
                }
                if (count($rows) != 1) {
                    NGEssentials::getInstance()->getLogger()->warning("GetSingle returned more than one row for $xuid $gameKey");
                    return;
                }
                $onReceive(Streak::FromSQL($rows[0]));
            },
            onError: $onError,
        );
    }
}
