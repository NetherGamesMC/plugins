<?php
declare(strict_types=1);

namespace lobby\entity\minecraft\registry;

use Closure;
use lobby\features\secret\SecretData;
use lobby\Lobby;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;

class ConditionRegistry
{
    /** @var Closure[] */
    private static array $conditions = [];

    public static function register(Lobby $plugin): void
    {
        self::registerConditionalFunction("has_checkpoint", static function (Player $player, string $arg) use ($plugin) {
            return $plugin->getCheckpointManager()->hasReachedCheckpoint($player, $arg) ? "true" : "false";
        });

        self::registerConditionalFunction("has_secret", function (Player $player, string $arg) {
            return PlayerUtils::hasUnlockedToken($player, $arg) ? "true" : "false";
        });

        self::registerConditionalFunction("plays_archery", function (Player $player, string $arg) {
            foreach (Lobby::getInstance()->getFeaturesManager()->getShootingRanges() as $shootingRanges) {
                foreach ($shootingRanges as $shootingRange) {
                    if ($shootingRange->getPlayer() === $player) {
                        return "true";
                    }
                }
            }

            return "false";
        });

        self::registerConditionalFunction("has_all_secrets", static function (Player $player, string $arg) use ($plugin) {
            $playerUnlocks = $plugin->getNGEssentials()->getPlayerData()->getArray($player, PlayerData::LOBBY_COLLECTED_TOKENS);

            return (count($playerUnlocks) === count(SecretData::SECRET_STANDS)) ? "true" : "false";
        });
    }

    public static function registerConditionalFunction(string $idx, Closure $function): void
    {
        self::$conditions[$idx] = $function;
    }

    public static function getConditionalResult(string $idx, string $arg, Player $player): string
    {
        if (!array_key_exists($idx, self::$conditions)) return "null";

        $function = self::$conditions[$idx];

        return $function($player, $arg);
    }
}