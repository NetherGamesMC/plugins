<?php

declare(strict_types=1);

namespace lobby\features\parkour;

use lobby\entity\custom\IconMarker;
use lobby\features\secret\SecretData;
use lobby\utils\BaseTrait;
use lobby\utils\Items;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function array_key_exists;
use function count;

class Parkour
{
    use BaseTrait;

    public const RECORD = 0;
    public const START_TIME = 1;
    public const CHECK_POINT = 2;
    public const REACHED_CHECKPOINTS = 4;

    /** @var array */
    private array $parkourData = [];

    /** @var float */
    private float $serverRecord = PHP_INT_MAX;

    public function __construct()
    {
        $plugin = $this->getPlugin();
        $entityManager = $this->getNGEssentials()->getEntityManager();
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        $entityManager->addEntity(new FloatingText(new Location(-110.5, 48, 74.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Parkour Challenge", TextFormat::GREEN . "Start"));
        $entityManager->addEntity(new FloatingText(new Location(-126.5, 61, 111.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#1"));
        $entityManager->addEntity(new FloatingText(new Location(-131.5, 70, 125.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#2"));
        $entityManager->addEntity(new FloatingText(new Location(-167.5, 87, 145.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#3"));
        $entityManager->addEntity(new FloatingText(new Location(-222.5, 96, 145.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#4"));
        $entityManager->addEntity(new FloatingText(new Location(-207.5, 101, 165.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#5"));
        $entityManager->addEntity(new FloatingText(new Location(-195.5, 119, 166.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Checkpoint", TextFormat::AQUA . "#6"));
        $entityManager->addEntity(new FloatingText(new Location(-198.5, 140, 166.5, $defaultWorld, 0, 0), TextFormat::YELLOW . "Parkour Challenge", TextFormat::RED . "End"));

        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($defaultWorld): void {
            foreach ($defaultWorld->getPlayers() as $player) {
                if (($startTime = $this->getStartTime($player)) !== 0.0) {

                    /*if ($player->getAllowFlight() === true || $player->isFlying()) {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                    }*/

                    $player->sendJukeboxPopup(TextFormat::YELLOW . Utils::timeToHR(microtime(true) - $startTime));
                }
            }
        }), 20);

        MySQLCredentials::executeSelect("parkour.best", [], function (array $rows) {
            if (count($rows) > 0) {
                $this->serverRecord = $rows[0]["record"];
            }
        });
    }

    public function getStartTime(Player $player): float
    {
        return $this->parkourData[$player->getId()][self::START_TIME] ?? 0.0;
    }

    public function resetParkour(Player $player): void
    {
        $this->stopParkour($player);
        $player->teleport($this->getStartPoint());
    }

    public function stopParkour(Player $player, bool $finish = false): void
    {
        if ($this->isPlaying($player)) {
            if ($finish) {
                $time = microtime(true) - $this->getStartTime($player);

                if ($time >= 25 && $this->getCheckpoint($player) !== $this->getStartPoint()) {
                    $checkpointReached = count($this->parkourData[$player->getId()][self::REACHED_CHECKPOINTS]) === 6;
                    if ($checkpointReached) {
                        if (($isFirst = ($record = $this->getRecord($player)) === -1.0) || $time < $record) {
                            if ($time < $this->getServerRecord()) {
                                $player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "CONGRATULATIONS! " . TextFormat::RESET . TextFormat::GREEN . "You've broken a new server record for the parkour with a time of " . TextFormat::YELLOW . Utils::timeToHR($time) . TextFormat::GREEN . " - you can view the parkour leaderboards online at " . TextFormat::GOLD . "ngmc.co/a");
                                $this->serverRecord = $time;
                            } else {
                                $previousRecord = !$isFirst ? TextFormat::GREEN . ". Your previous time was " . TextFormat::YELLOW . Utils::timeToHR($record) : "";

                                $player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "NEW PERSONAL RECORD! " . TextFormat::RESET . TextFormat::GREEN . "You've broken your personal record for the parkour with a time of " . TextFormat::YELLOW . Utils::timeToHR($time) . $previousRecord . TextFormat::GREEN . ".");
                            }

                            $this->parkourData[$player->getId()][self::RECORD] = $time;
                            MySQLCredentials::executeInsert("parkour.save", ["xuid" => $player->getXuid(), "record" => $time]);
                        } else {
                            $player->sendMessage(TextFormat::GREEN . "You completed the parkour in " . TextFormat::YELLOW . Utils::timeToHR($time) . TextFormat::GREEN . ". Your record is " . TextFormat::YELLOW . Utils::timeToHR($this->getRecord($player)) . TextFormat::GREEN . ".");
                        }

                        if (!PlayerUtils::hasUnlockedToken(player: $player, tokenId: SecretData::VOLCANO)) {
                            $token = new IconMarker(new Location(-198.5, 140.5, 169.5, $player->getWorld(), 0, 0), SecretData::VOLCANO, true);
                            $token->addTo($player);
                            $token->spawnTo($player);
                        }
                    } else {
                        $player->sendMessage("§cIn order to prevent cheating you have to check the last checkpoint in order to finish the parkour. Please try again.");
                    }
                } else {
                    $player->sendMessage(TextFormat::RED . "Cheating on the parkour course is not allowed!");
                }
            } else {
                $player->teleport($this->getNGEssentials()->getServerManager()->getSpawn());
            }

            Items::setLobbyInventory($player);

            unset($this->parkourData[$player->getId()][self::CHECK_POINT], $this->parkourData[$player->getId()][self::START_TIME]);

            if ($player->isConnected()) {
                if ($player->isAdventure() || $player->isSurvival()) {
                    $playerData = $this->getNGEssentials()->getPlayerData();
                    if ($playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK) && $player->hasPermission(Permissions::RANK_ULTRA)) {
                        $player->setAllowFlight(true);
                    }
                }
            }
        }
    }

    public function isPlaying(Player $player): bool
    {
        return $this->getStartTime($player) !== 0.0;
    }

    public function getCheckPoint(Player $player): Location
    {
        return $this->parkourData[$player->getId()][self::CHECK_POINT] ?? $this->getStartPoint();
    }

    public function getStartPoint(): Location
    {
        return new Location(-109, 47, 73, $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld(), 45, 0.0);
    }

    public function getRecord(Player $player): float
    {
        return $this->parkourData[$player->getId()][self::RECORD] ?? -1.0;
    }

    public function getServerRecord(): float
    {
        return $this->serverRecord;
    }

    public function startParkour(Player $player): void
    {
        if ($this->isPlaying($player)) {
            unset($this->parkourData[$player->getId()][self::CHECK_POINT]);
        } else {
            $petsManager = $this->getNGEssentials()->getPlayerManager()->getWorldFeatures()->getManager()->getPetsManager();
            $petsManager->removePet($player);
            $player->removeCurrentWindow();

            $player->setAllowFlight(false);
            $player->setFlying(false);
            $player->setGliding(false);

            Items::setParkourInventory($player);
            $player->sendMessage(TextFormat::GREEN . "You've started the parkour attempt!");
        }

        $this->parkourData[$player->getId()][self::START_TIME] = microtime(true);
        $this->parkourData[$player->getId()][self::REACHED_CHECKPOINTS] = [];
    }

    public function setCheckPoint(Player $player, int $checkpoint): void
    {
        if ($this->isPlaying($player) && !array_key_exists($checkpoint, $this->parkourData[$player->getId()][self::REACHED_CHECKPOINTS])) {
            $this->parkourData[$player->getId()][self::CHECK_POINT] = $player->getLocation();

            $time = microtime(true) - $this->getStartTime($player);
            $this->parkourData[$player->getId()][self::REACHED_CHECKPOINTS][$checkpoint] = $time;
            $player->sendMessage(TextFormat::GREEN . "You reached checkpoint #$checkpoint in " . Utils::timeToHR($time));
        }
    }

    public function loadParkourResults(Player $player): void
    {
        MySQLCredentials::executeSelect("parkour.load", ["xuid" => $player->getXuid()], function (array $rows) use ($player) {
            if ($player->isConnected() && count($rows) > 0) {
                $this->parkourData[$player->getId()][self::RECORD] = $rows[0]["record"];
            }
        });
    }

    public function unloadParkourResults(Player $player): void
    {
        unset($this->parkourData[$player->getId()]);
    }
}