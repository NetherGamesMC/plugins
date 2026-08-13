<?php

namespace meltdown\arena;

use libminigames\Arena;
use libminigames\tasks\CountDownTask;
use meltdown\arena\handler\BlockHandler;
use meltdown\arena\handler\PowerupHandler;
use meltdown\arena\handler\ScoreboardHandler;
use meltdown\Meltdown;
use meltdown\task\MatchTimeTask;
use meltdown\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function count;

class MDArena extends Arena
{
    public const MODE_NORMAL = 0;

    /** @var ScoreboardHandler */
    private ScoreboardHandler $scoreboardHandler;

    /** @var BlockHandler */
    private BlockHandler $blockHandler;

    /** @var PowerupHandler */
    private PowerupHandler $powerupHandler;

    /** @var array<string, int> player name => minutes played */
    private array $minutesPlayed = [];

    public function __construct(Meltdown $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);

        $this->statsData = new StatsData($plugin->getModeName($modeId));
        $this->listener = new MDArenaListener($this);

        $arenas = $plugin->getMaps(!$privateGame);
        $maps = array_rand($arenas, min(5, count($arenas)));

        if (is_array($maps)) {
            foreach ($maps as $map) {
                $this->maps[] = $arenas[$map];
            }
        } else {
            $this->maps[] = $arenas[$maps];
        }
    }

    public function bootMinigame(): void
    {
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    public function getPlugin(): Meltdown
    {
        /** @var Meltdown $plugin */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $plugin = parent::getPlugin();
        return $plugin;
    }

    /**
     * @param World $world
     */
    public function setupMapFeatures(World $world): void
    {
        $this->scoreboardHandler = new ScoreboardHandler($this);
        $this->blockHandler = new BlockHandler($this);
        $this->powerupHandler = new PowerupHandler($this);

        parent::setupMapFeatures($world);
    }

    public function startGame(): void
    {
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "Meltdown", true);
        $this->broadcastMessage("", true);
        $this->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "Run quickly, before the blocks under you melt!", true);
        $this->broadcastMessage("", true);
        $this->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "The last one standing wins!", true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);

        $this->getScoreboardHandler()->setFirstScoreboard();

        $arenaConfig = $this->getPlugin()->getArenaConfig();
        $spawn = $arenaConfig->getSpawn($this);
        $radius = $arenaConfig->getRadius($this) - 10;
        foreach ($this->getAlivePlayers() as $player) {
            $playerSpawn = Location::fromObject(
                $spawn->add(0.5 + mt_rand(-$radius, $radius), 0, 0.5 + mt_rand(-$radius, $radius)),
                $spawn->getWorld(),
                $spawn->getYaw(),
                $spawn->getPitch()
            );

            /** @var NGPlayer $player */
            $player->setEnergized();
            $player->teleport($playerSpawn);
        }
    }

    /**
     * @return ScoreboardHandler
     */
    public function getScoreboardHandler(): ScoreboardHandler
    {
        return $this->scoreboardHandler;
    }

    /**
     * @return MDArenaConfig
     */
    public function getArenaConfig(): MDArenaConfig
    {
        return $this->getPlugin()->getArenaConfig();
    }

    public function onPlayerDeath(Player $player): void
    {
        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsData::MD_DEATHS);

        $player->sendTitle('§l§cYOU DIED!', '§7You are now a spectator.');
        $player->setHealth($player->getMaxHealth());
        $player->teleport($this->getWorld()->getSafeSpawn());
        $this->addSpectator($player);

        $this->getScoreboardHandler()->updatePlayerCount();

        $alive = count($this->getAlivePlayers());
        $max = $this->getMaxSize();
        $this->broadcastMessage("§7{$player->getName()} §eeliminated! (§b{$alive}§e/§b{$max}§e)!", true);
    }

    public function getMaxSize(): int
    {
        return Meltdown::$MAX_SIZE;
    }

    public function addKill(Player $killer, Player $killed): void
    {
        $statsData = $this->getStatsData();
        $statsData->addKill($killer, $killed, StatsData::MD_KILLS);

        if ($killer->isOnline()) {
            $location = $killer->getLocation();
            $killer->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                "random.pop",
                $location->getX(),
                $location->getY(),
                $location->getZ(),
                1,
                1,
                null
            ));
            $this->broadcastMessage(TextFormat::YELLOW . $killer->getName() . TextFormat::WHITE . " knocked down " . TextFormat::YELLOW . $killed->getName() . TextFormat::WHITE . " a level ({$statsData->getValue($killer, StatsData::MD_KILLS)} players knocked)");
        }
    }

    /**
     * @param array<self::DATA_*, array<array{string, int}>> $data
     */
    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        $statsData = $this->getStatsData();

        $minutesPlayed = $this->minutesPlayed[$player->getName()] ?? 0;
        if ($minutesPlayed > 0) {
            $data[self::DATA_XP][] = [
                $minutesPlayed . " minutes played",
                $minutesPlayed
            ];
        }

        $killCount = $statsData->getValue($player, StatsData::MD_KILLS);
        if ($killCount > 0) {
            $data[self::DATA_XP][] = [
                $killCount . " kills",
                $killCount
            ];
        }

        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                "Win",
                2
            ];
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    /**
     * @param Player $player
     */
    public function incrementMinutesPlayed(Player $player): void
    {
        if (!isset($this->minutesPlayed[$player->getName()])) {
            $this->minutesPlayed[$player->getName()] = 0;
        }
        $this->minutesPlayed[$player->getName()]++;
    }

    /**
     * @return BlockHandler
     */
    public function getBlockHandler(): BlockHandler
    {
        return $this->blockHandler;
    }

    /**
     * @return PowerupHandler
     */
    public function getPowerupHandler(): PowerupHandler
    {
        return $this->powerupHandler;
    }

    /**
     * @return int
     */
    public function getMinimumPlayers(): int
    {
        return Meltdown::$MINIMUM_PLAYERS;
    }

    public function getListener(): MDArenaListener
    {
        /** @var MDArenaListener $listener */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $listener = $this->listener;
        return $listener;
    }
}