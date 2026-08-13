<?php

declare(strict_types=1);


namespace libReplay\session\replay;


use DateInterval;
use DateTime;
use libasyncio\FileCopyAsyncTask;
use libasyncio\FileDeleteAsyncTask;
use libminigames\utils\Items;
use libReplay\protocol\BlockChangePacket;
use libReplay\protocol\PlayerInformationPacket;
use libReplay\Replays;
use libReplay\session\replay\tasks\DownloadTask;
use libReplay\session\replay\tasks\ReplayTickTask;
use libReplay\session\replay\utils\ReplayInfo;
use libVanilla\VanillaPlugin;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\thread\NGThreadPool;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\scoreboard\Scoreboard;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;
use function array_diff;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class ReplayManager
{
    /** @var self|null */
    private static ?self $instance = null;
    /** @var Replay[] */
    private array $replays = [];
    /** @var ReplayTickTask */
    private ReplayTickTask $tickTask;
    /** @var Replays */
    private Replays $manager;
    /** @var PacketPool */
    private PacketPool $packetPool;
    /** @var Scoreboard */
    private Scoreboard $scoreboard;

    public function __construct(Replays $manager)
    {
        $packetPool = PacketPool::getInstance();
        $packetPool->registerPacket(new BlockChangePacket());
        $packetPool->registerPacket(new PlayerInformationPacket());

        $this->manager = $manager;
        $this->packetPool = $packetPool;
        $this->tickTask = new ReplayTickTask($this);

        $this->scoreboard = new Scoreboard(TextFormat::GOLD . TextFormat::BOLD . 'REPLAY', SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, SetDisplayObjectivePacket::SORT_ORDER_DESCENDING);

        $plugin = $manager->getPlugin();
        $plugin->getServer()->getPluginManager()->registerEvents(new ReplayListener($this), $plugin);

        foreach ([
                     VanillaPlugin::FISHING_ROD(),
                     VanillaPlugin::CROSSBOW(),
                     VanillaPlugin::SHIELD(),
                     VanillaPlugin::TRIDENTS(),
                 ] as $feature) {
            $feature->register($plugin);
        }

        self::$instance = $this;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public static function removeUnusedReplays(): void
    {
        $time = new DateTime();
        $time->sub(new DateInterval('P7D'));

        MySQLCredentials::executeGeneric('replay.remove_expired', ['time' => $time->getTimestamp()]);
    }

    public function loadReplay(Player $player, int $replayId): void
    {
        $onFailure = function () use ($player): void {
            if (!$player->isConnected()) {
                return;
            }
            $player->sendMessage(TextFormat::RED . 'Replay not found.');

            $ess = $this->getManager()->getPlugin();
            $ess->getPlayerManager()->transferPlayer($player);
        };

        self::getReplayInfo($replayId, function (ReplayInfo $info) use ($player, $onFailure): void {
            $this->loadWorld($info->getServerType(), $info->getMapName(), 'Replay-' . $player->getName(), function (World $replayWorld) use ($player, $info, $onFailure): void {
                $this->loadReplayData($info->getReplayId(), function (string $payload) use ($player, $info, $replayWorld, $onFailure): void {
                    if ($payload === '' || !$player->isConnected()) {
                        $this->unloadWorld($replayWorld);
                        $onFailure();
                        return;
                    }

                    $this->setupPlayer($player, $replayWorld, $info);
                    $this->startReplay($replayWorld, $info, $payload);
                });
            }, $onFailure);
        }, $onFailure);
    }

    private function getManager(): Replays
    {
        return $this->manager;
    }

    public static function getReplayInfo(int $replayId, callable $onSuccess, callable $onFailure): void
    {
        Utils::validateCallableSignature(function (ReplayInfo $result): void {}, $onSuccess);
        Utils::validateCallableSignature(function (): void {}, $onFailure);

        MySQLCredentials::executeSelect('replay.load_by_replay_id', ['replay_id' => $replayId], static function (array $rows) use ($onSuccess, $onFailure): void {
            if (isset($rows[0])) {
                $row = $rows[0];

                /** @var string[] $players */
                $players = json_decode($row['players'], false, 512, JSON_THROW_ON_ERROR);

                $onSuccess(ReplayInfo::create(
                    $row['replay_id'],
                    $row['protocol_id'] ?? ProtocolInfo::CURRENT_PROTOCOL,
                    $row['server_type'],
                    $row['game_type'],
                    $row['map_name'],
                    $players,
                    $row['time'],
                ));
            } else {
                $onFailure();
            }
        });
    }

    private function loadWorld(string $serverType, string $mapName, string $worldName, callable $onSuccess, callable $onFailure): void
    {
        Utils::validateCallableSignature(function (World $replayWorld): void {}, $onSuccess);
        Utils::validateCallableSignature(function (): void {}, $onFailure);

        $ess = $this->getManager()->getPlugin();

        NGThreadPool::getInstance()->submitTask(new FileCopyAsyncTask(Path::join($ess->getDataFolder(), 'maps', $serverType, $mapName), Path::join($ess->getServer()->getDataPath(), 'worlds', $worldName), function () use ($ess, $worldName, $onFailure, $onSuccess): void {
            $worldManager = $ess->getServer()->getWorldManager();

            $worldManager->loadWorld($worldName);
            $replayWorld = $worldManager->getWorldByName($worldName);

            if ($replayWorld === null) {
                NGThreadPool::getInstance()->submitTask(new FileDeleteAsyncTask(Path::join($ess->getServer()->getDataPath(), 'worlds', $worldName)));

                $onFailure();
            } else {
                $replayWorld->setTime(World::TIME_DAY);
                $replayWorld->stopTime();

                foreach ($replayWorld->getEntities() as $entity) {
                    if (!$entity instanceof Player) {
                        $entity->flagForDespawn();
                    }
                }

                $onSuccess($replayWorld);
            }
        }));
    }

    private function loadReplayData(int $replayId, callable $callable): void
    {
        NGThreadPool::getInstance()->submitTask(new DownloadTask($replayId, $callable));
    }

    private function unloadWorld(World $replayWorld): void
    {
        $server = $this->getManager()->getPlugin()->getServer();
        $worldManager = $server->getWorldManager();
        $worldManager->unloadWorld($replayWorld);

        NGThreadPool::getInstance()->submitTask(new FileDeleteAsyncTask(Path::join($server->getDataPath(), 'worlds', $replayWorld->getFolderName())));
    }

    private function setupPlayer(Player $player, World $replayWorld, ReplayInfo $info): void
    {
        $player->teleport($replayWorld->getSpawnLocation());
        $player->setGamemode(GameMode::SPECTATOR);
        $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 1, false));

        $time = $info->getTime();
        $this->getScoreboard()->addPlayer($player);
        $this->getScoreboard()->setLines([$player], [
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
            2 => '',
            3 => 'Mode: ' . TextFormat::GREEN . $info->getGameType(),
            4 => 'Game: ' . TextFormat::GREEN . ServerManager::getName($info->getServerType()),
            5 => 'Time: ' . TextFormat::GREEN . '0:00',
            6 => '',
            7 => 'Date: ' . TextFormat::GREEN . $time->format('d/m/Y H:i') . ' (UTC)',
            8 => '',
            9 => 'Replay ID: ' . TextFormat::GREEN . $info->getReplayId(),
            10 => ''
        ]);

        $player->getInventory()->setHeldItemIndex(3);

        if (Permissions::isStaff($player)) {
            $player->getInventory()->setContents([
                0 => Items::getSpectatorCompass(),
                1 => LobbyItems::getNoClipToggleItem(),

                4 => LobbyItems::getResumeReplayTorch(),
                5 => LobbyItems::getStaffPortalItem(),

                7 => LobbyItems::getSpeedReplayFeather(),
                8 => LobbyItems::getSpectatorBed()
            ]);
        } else {
            $player->getInventory()->setContents([
                0 => Items::getSpectatorCompass(),
                1 => LobbyItems::getNoClipToggleItem(),

                4 => LobbyItems::getResumeReplayTorch(),

                7 => LobbyItems::getSpeedReplayFeather(),
                8 => LobbyItems::getSpectatorBed()
            ]);
        }
    }

    public function getScoreboard(): Scoreboard
    {
        return $this->scoreboard;
    }

    public function startReplay(World $world, ReplayInfo $info, string $payload): void
    {
        $this->replays[$world->getId()] = new Replay($world, $info, $payload, $this->packetPool);

        $this->createTask();
    }

    private function createTask(): void
    {
        if ($this->tickTask->getHandler() === null) {
            $this->getManager()->getPlugin()->getScheduler()->scheduleDelayedRepeatingTask($this->tickTask, 1, 1);
        }
    }

    /**
     * @return Replay[]
     */
    public function getReplays(): array
    {
        return $this->replays;
    }

    public function stopReplay(Player $player, World $world): void
    {
        if (($replay = $this->getReplay($world)) !== null && empty(array_diff($world->getPlayers(), [$player]))) {
            $replay->stop();
            unset($this->replays[$world->getId()]);

            $this->unloadWorld($world);
        }
    }

    public function getReplay(World $world): ?Replay
    {
        return $this->replays[$world->getId()] ?? null;
    }
}