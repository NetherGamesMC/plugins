<?php
declare(strict_types=1);

namespace uhc\game;

use libminigames\TeamArena;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\Entity;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\GeneratorManagerEntry;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use uhc\game\scenario\base\Scenario;
use uhc\game\scenario\base\ScenarioRegistry;
use uhc\task\CountDownTask;
use uhc\task\UHCMatchTask;
use uhc\UHC;
use uhc\utils\StatsData;
use uhc\voting\Items;
use function array_keys;
use function array_search;
use function array_slice;
use function arsort;
use function count;
use function in_array;
use function mt_rand;

class UHCArena extends TeamArena
{
    private const MAX_SCENARIOS = 3;

    public const LINE_TIMER = 7;
    public const LINE_KILLS = 5;
    public const LINE_ALIVE = 4;
    public const LINE_BORDER = 3;

    /** @var Player[] */
    private array $playersToTeleport = [];
    /** @var string[][] */
    private array $votedScenarios = [];
    /** @var string[] */
    private array $enabledScenarios = [];
    /** @var bool */
    private bool $pvpEnabled = false;
    /** @var bool */
    private bool $scattered = false;
    /** @var bool */
    private bool $generated = false;
    /** @var Border */
    private Border $border;

    public function __construct(UHC $plugin, int $modeId, int $id, bool $privateGame = false)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);
        $this->border = new Border($this);
        $this->listener = new UHCArenaListener($this);

        $this->statsData = new StatsData($plugin->getModeName($modeId));

        // Thanks Skywars
        for ($i = 0; $i <= 11; $i++) {
            $this->teams[$i] = new UHCTeam($this, $i);
        }
    }

    public function isPvPEnabled(): bool
    {
        return $this->pvpEnabled;
    }

    public function setPvPEnabled(bool $status): void
    {
        $this->pvpEnabled = $status;
    }

    public function bootMinigame(): void
    {
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    public function getMapName(): string
    {
        return "UHC";
    }

    public function checkMapVotes(): void
    {
        $this->broadcastMessage("§eThe map is being generated, this may take a while...");
    }

    public function setupMap(): void
    {
        $worldName = 'Match-' . $this->getPlugin()->getMinigameTag() . '-' . $this->getId();

        /** @var GeneratorManager $generatorManager */
        $generatorManager = GeneratorManager::getInstance();
        /** @var GeneratorManagerEntry $generatorEntry */
        $generatorEntry = $generatorManager->getGenerator("vanilla_overworld");
        $options = WorldCreationOptions::create()->setGeneratorOptions('isUHC,1')->setGeneratorClass($generatorEntry->getGeneratorClass());

        $this->getPlugin()->getLogger()->info("Generating world $worldName, seed:" . $options->getSeed());

        $worldManager = $this->getPlugin()->getServer()->getWorldManager();
        $worldManager->generateWorld($worldName, $options, false);

        /** @var World $world */
        $world = $worldManager->getWorldByName($worldName);
        $worldSpawn = $world->getSpawnLocation();
        $spawnX = $worldSpawn->getFloorX();
        $spawnZ = $worldSpawn->getFloorZ();
        $world->orderChunkPopulation($spawnX >> 4, $spawnZ >> 4, null)
            ->onCompletion(function () use ($world): void {
                $world->startTime();
                $this->broadcastMessage("§eThe map has been generated!");
                $this->generated = true;
                $this->start();
            }, static function () {
            });
    }

    public function isGenerated(): bool
    {
        return $this->generated;
    }

    /**
     * Hack override to prevent the lobby world from being removed before players are scattered.
     */
    public function start(): void
    {
        if (!$this->scattered) {
            if (count($this->playersToTeleport) === 0) {
                $this->broadcastMessage("§eScattering players, this may take a while...");
                $plugin = $this->getPlugin();
                $this->world = $plugin->getServer()->getWorldManager()->getWorldByName('Match-' . $plugin->getMinigameTag() . '-' . $this->getId());
                foreach ($this->getAlivePlayers() as $player) {
                    $this->playersToTeleport[] = $player;
                    $player->setNoClientPredictions(true);
                }
                $this->scatter();
            }
        } else {
            $this->broadcastMessage("§eScattered all players! Starting...");
            parent::start();
        }
    }

    private function scatter(): void
    {
        $this->getScoreboard()->setLine($this->getAlivePlayers(), 5, CustomIcon::HOURGLASS . "§aScattering (" . count($this->playersToTeleport) . ")");
        if (count($this->playersToTeleport) < 1) {
            $this->scattered = true;
            $this->start();
            return;
        }

        $player = array_shift($this->playersToTeleport);
        if (!$player->isConnected()) {
            $this->scatter();
            return;
        }

        $randomX = mt_rand(-$this->getBorder()->getSize(), $this->getBorder()->getSize());
        $randomZ = mt_rand(-$this->getBorder()->getSize(), $this->getBorder()->getSize());
        $this->getWorld()->orderChunkPopulation($randomX >> 4, $randomZ >> 4, null)
            ->onCompletion(function () use ($player, $randomX, $randomZ): void {
                $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $randomX, $randomZ): void {
                    $this->processPopulation($player, $randomX, $randomZ);
                }), 2);
            }, function () use ($player) {
                /** @phpstan-ignore-next-line */
                if (!$player->isConnected()) {
                    return;
                }
                $this->playersToTeleport[] = $player;
            });
    }

    public function processPopulation(Player $player, int $randomX, int $randomZ): void
    {
        if (!$player->isConnected() || ($team = $this->getTeamNull($player)) === null) {
            return;
        }

        $y = World::Y_MAX;
        $safe = false;
        while ($y > 60) {
            $blockAt = $this->getWorld()->getBlockAt($randomX, $y, $randomZ);
            if (!$blockAt->isSolid()) {
                $y--;
                continue;
            }

            $safe = true;
            break;
        }

        if ($safe) {
            $player->teleport($pos = new Position($randomX, $y + 1, $randomZ, $this->getWorld()));

            if (!$this->isSoloGame()) {
                foreach ($team->getPlayers() as $teammate) {
                    if ($player->getXuid() === $teammate->getXuid()) {
                        continue;
                    }
                    $teammate->teleport($pos);
                    $key = array_search($teammate, $this->playersToTeleport, true);
                    unset($this->playersToTeleport[$key]);
                }
            }
        } else {
            array_unshift($this->playersToTeleport, $player);
        }

        $this->scatter();
    }

    public function getBorder(): Border
    {
        return $this->border;
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);
        $player->getInventory()->setItem($this->isSoloGame() ? Items::EXTRA_SOLO_ITEM_0 : Items::EXTRA_ITEM_1, Items::getScenarios());
    }

    public function startGame(): void
    {
        /** @var UHCTeam $aliveTeam */
        foreach ($this->getAliveTeams() as $aliveTeam) {
            /** @var NGPlayer $player */
            foreach ($aliveTeam->getAlivePlayers() as $player) {
                $player->setGamemode(GameMode::SURVIVAL);
                $player->setEnergized();
                $player->toggleGameRule("showcoordinates", true);
                $player->setNoClientPredictions(false);

                $aliveTeam->addToXuidCache($player->getXuid());
            }

            $aliveTeam->sendScoreboard();
        }

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new UHCMatchTask($this), 20);
    }

    /**
     * @param Player $player
     * @param string[] $scenarios
     */
    public function addTypeVote(Player $player, array $scenarios): void
    {
        $this->votedScenarios[$player->getXuid()] = $scenarios;
    }

    public function checkTypeVotes(): void
    {
        $countedScenarios = [];
        foreach ($this->votedScenarios as $scenarioArray) {
            foreach ($scenarioArray as $scenario) {
                $countedScenarios[$scenario] = isset($countedScenarios[$scenario]) ? ++$countedScenarios[$scenario] : 1;
            }
        }

        arsort($countedScenarios);
        $winners = array_slice($countedScenarios, 0, self::MAX_SCENARIOS);
        if (count($winners) < self::MAX_SCENARIOS) {
            $allScenarios = [];
            foreach (ScenarioRegistry::getAll() as $scenario) {
                if ($scenario->isAlwaysActive()) {
                    continue;
                }
                $allScenarios[$scenario->getName()] = $scenario->getName();
            }

            foreach ($winners as $winner => $votes) {
                unset($allScenarios[$winner]);
            }

            foreach ((array)array_rand($allScenarios, self::MAX_SCENARIOS - count($winners)) as $scenario) {
                $winners[ScenarioRegistry::fromString((string)$scenario)->getName()] = 0;
            }
        }

        $this->enabledScenarios = array_keys($winners);
        foreach ($winners as $winnerName => $votes) {
            $registry = ScenarioRegistry::fromString($winnerName);
            if ($votes > 0) {
                $this->broadcastMessage("§a{$registry->getDisplayName()}§e won with §a{$votes}§e vote(s)!", true);
            } else {
                $this->broadcastMessage("§a{$registry->getDisplayName()}§e was randomly selected!", true);
            }
        }

        foreach (ScenarioRegistry::getAll() as $scenario) {
            if ($scenario->isAlwaysActive()) {
                $this->enabledScenarios[] = $scenario->getName();
            }
        }
    }

    /**
     * @return string[]
     */
    public function getEnabledScenarios(): array
    {
        return $this->enabledScenarios;
    }

    public function isScenarioEnabled(Scenario $scenario): bool
    {
        return in_array($scenario->getName(), $this->enabledScenarios, true);
    }

    /**
     * @param Player $player
     * @return array|string[]
     */
    public function getVotedScenariosFromPlayer(Player $player): array
    {
        return $this->votedScenarios[$player->getXuid()] ?? [];
    }

    public function onPlayerDeath(Player $victim, ?Entity $killer = null): void
    {
        if ($killer instanceof Player) {
            $this->addElimination($killer, $victim);
            $this->playKillCosmetics($killer);
        }

        /** @var UHCTeam $team */
        $team = $this->getTeam($victim);
        $team->removeFromXuidCache($victim->getXuid());

        foreach ($victim->getInventory()->getContents() as $drops) {
            $this->getWorld()->dropItem($victim->getPosition(), $drops);
        }

        foreach ($victim->getArmorInventory()->getContents() as $drops) {
            $this->getWorld()->dropItem($victim->getPosition(), $drops);
        }

        $this->addSpectator($victim);
        $this->getStatsData()->addValue($victim, StatsData::DEATHS);
        $this->getStatsData()->addValue($victim, StatsData::UHC_DEATHS);

        $this->getScoreboard()->setLine($this->getPlayers(), self::LINE_ALIVE, CustomIcon::STEVE_HEAD . "§a" . count($this->getAlivePlayers()));
    }

    public function addElimination(Player $player, Player $victim): void
    {
        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::KILLS);
        $statsData->addKill($player, $victim, StatsData::UHC_KILLS);

        $this->getScoreboard()->setLine([$player], self::LINE_KILLS, CustomIcon::KILLS . $statsData->getValue($player, StatsData::UHC_KILLS));
    }

    public function getMinimumPlayers(): int
    {
        return $this->getModeId() === TeamArena::MODE_TRIOS ? 6 : ($this->getModeId() === TeamArena::MODE_SQUADS ? 8 : 4);
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        $guildXP = true;
        $modeId = $this->getModeId();

        if (($kills = $this->getStatsData()->getValue($player, StatsData::UHC_KILLS)) > 0) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . 'GAME SUMMARY:');
            $player->sendMessage(CustomIcon::SWORD . $kills . ' Kill' . ($kills > 1 ? 's' : ''));

            $data[self::DATA_CREDITS][] = [
                $kills . ' Kill' . ($kills > 1 ? 's' : ''),
                $kills * (match ($modeId) {
                    TeamArena::MODE_SOLO => 8,
                    TeamArena::MODE_DOUBLES => 10,
                    default => 8
                })
            ];
        }

        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                "Win",
                12
            ];
        }

        parent::addParticipation($player, $data, $guildXP);
    }
}
