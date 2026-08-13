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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials;

use Closure;
use ErrorException;
use libminigames\Minigame;
use lobby\Lobby;
use NetherGames\NGEssentials\events\NGRestartEvent;
use NetherGames\NGEssentials\events\NGStartDrainingEvent;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\servers\Cluster;
use NetherGames\NGEssentials\servers\Server;
use NetherGames\NGEssentials\servers\ServersCluster;
use NetherGames\NGEssentials\tasks\ClusterQueueTask;
use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function count;
use function explode;
use function function_exists;
use function getenv;
use function implode;
use function in_array;
use function is_numeric;
use function mt_rand;
use function pcntl_signal;
use function putenv;
use function random_int;
use function strtolower;
use function strtoupper;
use function ucfirst;
use const SIGTERM;

class ServerManager extends BaseClass
{
    public const BUTTONS = [
        'Skywars' => [
            'serverType' => self::SW,
            'gameTypes' => [
                self::GAME_TYPE_SOLO,
                self::GAME_TYPE_DOUBLES,
                self::GAME_TYPE_DUELS_SOLO,
                self::GAME_TYPE_DUELS_DOUBLES
            ],
            'icon' => 'textures/ui/skywars'
        ],
        'Bedwars' => [
            'serverType' => self::BW,
            'gameTypes' => [
                self::GAME_TYPE_SOLO,
                self::GAME_TYPE_DOUBLES,
                self::GAME_TYPE_SQUADS,
                self::GAME_TYPE_DUELS_SOLO,
                self::GAME_TYPE_DUELS_DOUBLES
            ],
            'icon' => 'textures/ui/bedwars'
        ],
        'Conquests' => [
            'serverType' => self::CQ,
            'gameTypes' => [
                self::GAME_TYPE_MEGA
            ],
            'icon' => 'textures/ui/conquests'
        ],
        'UHC' => [
            'serverType' => self::UHC,
            'gameTypes' => [
                self::GAME_TYPE_SOLO,
                self::GAME_TYPE_DOUBLES
            ],
            'icon' => 'textures/ui/uhc'
        ],
        'Murder Mystery' => [
            'serverType' => self::MM,
            'gameTypes' => [
                self::GAME_TYPE_CLASSIC,
                self::GAME_TYPE_INFECTION
            ],
            'icon' => 'textures/ui/murdermystery'
        ],
        'Survival Games' => [
            'serverType' => self::SG,
            'icon' => 'textures/ui/survivalgames'
        ],
        'Skyblock' => [
            'serverType' => self::SB,
            'gameTypes' => [
                self::GAME_TYPE_AGORA,
                self::GAME_TYPE_SKYLAND,
            ],
            'icon' => 'textures/ui/skyblock'
        ],
        'Arcade' => [
            'teleport' => ServerManager::AC,
            'icon' => 'textures/ui/arcade'
        ],
        'Creative' => [
            'serverType' => self::CREATIVE,
            'icon' => 'textures/ui/creative'
        ],
        'Factions' => [
            'serverType' => self::FACTIONS,
            'gameTypes' => [
                self::GAME_TYPE_FARLANDS,
                self::GAME_TYPE_BADLANDS,
            ],
            'customGameServer' => true,
            'icon' => 'textures/ui/factions'
        ],
        'Duels' => [
            'serverType' => self::DUELS,
            'gameTypes' => [
                self::GAME_TYPE_SOLO,
                self::GAME_TYPE_DOUBLES
            ],
            'icon' => 'textures/ui/duels'
        ],
        'The Bridge' => [
            'serverType' => self::TB,
            'gameTypes' => [
                self::GAME_TYPE_SOLO,
                self::GAME_TYPE_DOUBLES,
            ],
            'icon' => 'textures/ui/thebridge'
        ],
        //DUMMY MODES FOR AC
        //'Spleef' => [
        //    'dummy' => true,
        //    'serverType' => self::SP,
        //    'icon' => 'https://cdn.nethergames.org/img/forms/sp64.png',
        //],
        'Soccer' => [
            'dummy' => true,
            'serverType' => self::SC,
            'icon' => 'textures/ui/soccer',
        ],
        'Momma Says' => [
            'dummy' => true,
            'serverType' => self::MS,
            'icon' => 'textures/ui/mommasays',
        ],
        'Lobby' => [
            'serverType' => self::LOBBY,
            'icon' => 'textures/ui/hub'
        ],
    ];

    //public const SP = 'sp';
    public const AC = 'ac'; //DUMMY GAME MODE
    public const BW = 'bw';
    public const CQ = 'cq';
    public const CREATIVE = 'creative';
    public const DUELS = 'duels';
    public const FACTIONS = 'factions';
    public const LOBBY = 'lobby';
    public const MD = 'md';
    public const MM = 'mm';
    public const MS = 'ms';
    public const REPLAY = 'replay';
    public const SB = 'sb';
    public const SC = 'sc';
    public const SETUP = 'setup';
    public const SG = 'sg';
    public const SW = 'sw';
    public const TB = 'tb';
    public const UHC = 'uhc';

    public const GAME_TYPE_DOUBLES = 'Doubles';
    public const GAME_TYPE_TRIOS = 'Trios';
    public const GAME_TYPE_DUELS_DOUBLES = '2v2';
    public const GAME_TYPE_DUELS_SOLO = '1v1';
    public const GAME_TYPE_SOLO = 'Solo';
    public const GAME_TYPE_SQUADS = 'Squads';
    public const GAME_TYPE_MEGA = 'Mega';
    public const GAME_TYPE_CLASSIC = 'Classic';
    public const GAME_TYPE_INFECTION = 'Infection';
    public const GAME_TYPE_AGORA = 'Agora';
    public const GAME_TYPE_SKYLAND = 'Skyland';
    public const GAME_TYPE_FARLANDS = 'Farlands';
    public const GAME_TYPE_BADLANDS = 'Badlands';

    public const REGION_EU = 'EU';
    public const REGION_US = 'US';
    public const REGION_AP = 'AP';
    public const REGION_IND = 'IND';

    public const REGION_TO_NAME = [
        self::REGION_US => 'America',
        self::REGION_EU => 'Europe',
        self::REGION_AP => 'Asia Pacific',
        self::REGION_IND => 'India'
    ];

    /** @var bool */
    public static bool $draining = false;
    /** @var bool */
    public static bool $waitingForDrain = false;
    /** @var bool */
    public static bool $restarting = false;


    /** @var string */
    private string $serverUniqueId;
    /** @var int */
    private int $port;
    /** @var int */
    private int $id = 0;
    /** @var Cluster[] */
    private array $clusters = [];
    /** @var Location|null */
    private ?Location $spawnLocation = null;

    /** @var bool */
    private bool $touchQueuing = false;
    /** @var bool */
    private bool $queuing = false;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct($plugin);

        $invalidPodName = ($podName = getenv("POD_NAME")) === false;
        if ($invalidPodName) {
            $port = getenv("SERVER_PORT");
            $region = getenv('SERVER_REGION');
            $gameType = getenv('GAME_TYPE');
            $uniqueDeploymentId = getenv('SERVER_ID');

            if ($port === false || !is_numeric($port)) {
                $this->port = $plugin->getServer()->getPort();
            } else {
                $this->port = (int)$port;
            }

            if ($region === false) {
                $region = (string)$plugin->getConfig()->getNested('serverRegion', '');

                if ($region === '') {
                    $region = self::REGION_US;
                }
            }

            if ($gameType === false) {
                $gameType = (string)$plugin->getConfig()->getNested('gameType', '');
            }

            if ($uniqueDeploymentId === false) {
                $uniqueDeploymentId = (string)random_int(0, PHP_INT_MAX);
            }

            $serverType = (string)$plugin->getConfig()->getNested('serverType');
            $this->serverUniqueId = implode('-', [
                $region,
                strtolower($serverType),
                $gameType,
                '',
                $uniqueDeploymentId
            ]);
        } else {
            $this->port = 19132; //Pods have their own IP, so we can use 19132
            [$region, $serverType, $gameType, $replicaId, $uniqueDeploymentId] = explode('-', $podName);

            if ($gameType === 'na') {
                $gameType = '';
            }

            $this->serverUniqueId = implode('-', [
                strtoupper($region),
                $serverType,
                ucfirst($gameType),
                $replicaId,
                $uniqueDeploymentId
            ]);
        }
        putenv('POD_NAME=' . $this->getUniqueId());

        $plugin->getLogger()->info('§aBooting ' . $this->getUniqueId() . '...');

        $draining = function (): void {
            $this->setDraining();
        };

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, $draining);
        }

        $scheduler = $plugin->getScheduler();
        $scheduler->scheduleDelayedTask(new ClosureTask($draining), mt_rand(23 * 60 * 60 * 20, 25 * 60 * 60 * 20));
        if ($this->enableLobbyHandling()) {
            $scheduler->scheduleDelayedRepeatingTask(new ClusterQueueTask($this), 20, 20);
        }

        if (($serverType = $this->getServerType()) === self::LOBBY) {
            $clusterUniqueId = $serverType . '-' . $this->getGameType();

            $this->clusters[$clusterUniqueId] = new ServersCluster($clusterUniqueId);
        }
        if ($this->enableLobbyHandling() || $this->getServerType() === self::FACTIONS) {
            foreach ($this->getAllServerTypes() as $serverType) {
                $serverData = self::BUTTONS[self::getName($serverType)] ?? [];

                if ($serverData['customGameServer'] ?? false) {
                    // @phpstan-ignore-next-line
                    foreach ($serverData['gameTypes'] ?? [] as $gameType) {
                        $clusterUniqueId = $serverType . '-' . $gameType;

                        $this->clusters[$clusterUniqueId] = new ServersCluster($clusterUniqueId);
                    }
                }
            }
        }

        $plugin->getServer()->getNetwork()->setName($this->getMotd());
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getServer(string $uniqueId = ''): ?Server
    {
        if ($uniqueId === '') {
            return $this->getServer($this->getUniqueId());
        }

        try { // Make sure the uniqueId is valid
            [, $serverType, $gameType, ,] = explode('-', $uniqueId);
        } catch (ErrorException $e) {
            return null;
        }

        return $this->getCluster($serverType, $gameType)->getServer($uniqueId);
    }

    public function getUniqueId(): string
    {
        return $this->serverUniqueId;
    }

    public function getCluster(string $serverType = '', string $gameType = ''): Cluster
    {
        if ($serverType === '') {
            $serverType = $this->getServerType();
            $gameType = $this->getGameType();
        }

        return $this->getClusterByClusterUniqueId($serverType . '-' . $gameType);
    }

    public function getServerType(): string
    {
        return explode('-', $this->getUniqueId())[1];
    }

    public function getGameType(): string
    {
        return explode('-', $this->getUniqueId())[2];
    }

    public function getClusterByClusterUniqueId(string $clusterUniqueId): Cluster
    {
        if (!isset($this->clusters[$clusterUniqueId])) {
            $this->clusters[$clusterUniqueId] = new Cluster($clusterUniqueId);
        }

        return $this->clusters[$clusterUniqueId];
    }

    public function setDraining(): void
    {
        if (self::$draining) {
            return;
        }
        self::$draining = true;

        $plugin = $this->getPlugin();
        $server = $plugin->getServer();

        $plugin->getLogger()->info('§aDraining...');

        $promises = new ObjectSet();
        $event = new NGStartDrainingEvent($promises);
        $event->call();

        $startShutdown = function () use ($server, $plugin): void {
            self::$waitingForDrain = true;

            $directShutdown = static function () use ($server): void {
                self::$restarting = true;

                $event = new NGRestartEvent();
                $event->call();

                $server->shutdown();
            };

            if (count($server->getOnlinePlayers()) === 0) {
                $directShutdown();
            } else {
                if (in_array($this->getServerType(), [self::LOBBY, self::CREATIVE], true)) {
                    $delay = 5 * 60 * 20;
                } else {
                    $delay = 50 * 60 * 20;
                }

                $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask($directShutdown), $delay);
            }
        };

        if (count($prs = $promises->toArray()) > 0) {
            /** @phpstan-var non-empty-array<int, Promise<null>> $prs */
            $promise = Promise::all($prs);
            $promise->onCompletion($startShutdown, $startShutdown);
        } else {
            $startShutdown();
        }
    }

    public function enableLobbyHandling(): bool
    {
        return !$this->isMMOGame() && !$this->isMinigame() && !in_array($this->getServerType(), [self::REPLAY, self::SETUP], true);
    }

    public function isMMOGame(?string $serverType = null): bool
    {
        return in_array($serverType ?? $this->getServerType(), [self::SB, self::FACTIONS]);
    }

    public function isMinigame(): bool
    {
        return in_array($this->getServerType(), [self::BW, self::SG, self::TB, self::SW, self::DUELS, self::MM, self::SC, self::MS, self::UHC, self::CQ, self::MD, /*self::SP*/]);
    }

    public function getAllServerTypes(): array
    {
        return [
            self::BW,
            self::CQ,
            self::CREATIVE,
            self::DUELS,
            self::FACTIONS,
            self::LOBBY,
            //self::MD,
            self::MM,
            self::MS,
            self::REPLAY,
            self::SB,
            self::SC,
            self::SG,
            //self::SP,
            self::SW,
            self::TB,
            self::UHC,
        ];
    }

    public static function getName(string $serverType): string
    {
        return match ($serverType) {
            self::AC => 'Arcade',
            self::BW => 'Bedwars',
            self::CQ => 'Conquests',
            self::SG => 'Survival Games',
            self::MD => 'Meltdown',
            self::MM => 'Murder Mystery',
            self::MS => 'Momma Says',
            self::SB => 'Skyblock',
            self::SC => 'Soccer',
            self::SW => 'Skywars',
            self::TB => 'The Bridge',
            self::UHC => 'UHC',
            default => ucfirst($serverType),
        };
    }

    public function getMotd(): string
    {
        return '§k§b.§r §o§e§lN§6G§r§7: §bNG ' . self::getName($this->getServerType());
    }

    /**
     * THIS CAN BE USED IN EVERY THREAD :)
     *
     * @return string
     */
    public static function getServerUniqueId(): string
    {
        return getenv('POD_NAME');
    }

    public static function getIcon(string $serverType): string
    {
        return self::BUTTONS[self::getName($serverType)]['icon'] ?? self::BUTTONS[self::getName(self::LOBBY)]['icon'];
    }

    public function isTouchQueuing(): bool
    {
        return $this->touchQueuing;
    }

    public function isQueuing(): bool
    {
        return $this->queuing;
    }

    public function setQueuing(bool $queuing, bool $touchQueuing): void
    {
        $this->queuing = $queuing;
        $this->touchQueuing = $touchQueuing;
    }


    /**
     * This method can only be used on ServersClusters, where the server knows about the other servers
     * so it can calculate its own id
     *
     * @return int
     * @throws ErrorException
     */
    public function getId(): int
    {
        if ($this->getCluster() instanceof ServersCluster) {
            return $this->id;
        }

        throw new ErrorException("Can't call getId() on a server that doesn't know the id of other servers");
    }

    public function setId(int $id): void
    {
        $this->id = $id;

        $plugin = $this->getPlugin();
        /** @var World $defaultWorld */
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();
        $plugin->getServerData()->getScoreBoard()->setLine($defaultWorld->getPlayers(), 4, CustomIcon::WORLD . "Lobby: " . TextFormat::GREEN . TextFormat::GREEN . '#' . $id);
    }

    public function getMaxPlayers(): int
    {
        return match ($this->getServerType()) {
            self::SB => $this->getGameType() === self::GAME_TYPE_SKYLAND ? 30 : 60,
            self::FACTIONS => $this->getGameType() === self::GAME_TYPE_BADLANDS ? 40 : 60,
            self::LOBBY => 30,
            default => 50,
        };
    }

    public function enableLobbyFeatures(World $world): bool
    {
        return $this->enableLobbyHandling() && ($this->getServerType() === self::LOBBY || $world === $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld());
    }

    public function enableSocialManager(): bool
    {
        return $this->getServerType() !== self::FACTIONS;
    }

    public function enableCombatLogger(): bool
    {
        return NGEssentials::isInDevelopmentMode() || in_array($this->getServerType(), [self::SG, self::BW, self::MD, self::DUELS, self::FACTIONS, self::SB, self::SW, self::TB, self::UHC, self::CQ, self::SETUP]);
    }

    public function enableReplayServer(): bool
    {
        return $this->getServerType() === self::REPLAY || $this->isMinigame();
    }

    public function getQueuedCluster(Player $player): ?Cluster
    {
        foreach ($this->getClusters() as $cluster) {
            if (in_array($player, $cluster->getQueuedPlayers(), true)) {
                return $cluster;
            }
        }

        return null;
    }

    /**
     * Only used in lobby servers
     *
     * @return Cluster[]
     */
    public function getClusters(): array
    {
        return $this->clusters;
    }

    /**
     * @return Player[][]
     */
    public function getQueuedPlayers(): array
    {
        $players = [];

        foreach ($this->getClusters() as $cluster) {
            $players[$cluster->getUniqueId()] = $cluster->getQueuedPlayers();
        }

        return $players;
    }

    public function getLobbyPlugin(): ?Lobby
    {
        /** @var Lobby | null $plugin */
        $plugin = $this->getPlugin()->getServer()->getPluginManager()->getPlugin('Lobby');

        return $plugin;
    }

    public function getGamePlugin(): ?Minigame
    {
        if ($this->isMinigame()) {
            $plugin = $this->getPlugin()->getServer()->getPluginManager()->getPlugin([
                self::BW => 'xBedwars',
                self::CQ => 'xConquests',
                self::DUELS => 'xDuels',
                self::MM => 'xMurderMystery',
                self::MS => 'xMommaSays',
                self::SC => 'xSoccer',
                self::SG => 'xSurvivalGames',
                //self::SP => 'xSpleef',
                self::SW => 'xSkywars',
                self::TB => 'xTheBridge',
                self::UHC => 'xUHC',
                self::MD => 'xMeltdown',
            ][$this->getServerType()]);

            if ($plugin instanceof Minigame) {
                return $plugin;
            }
        }

        return null;
    }

    public function getColor(): string
    {
        return match ($this->getServerType()) {
            self::MS, self::SC, self::MD => TextFormat::DARK_RED,
            self::BW, self::CQ => TextFormat::RED,
            self::CREATIVE => TextFormat::GREEN,
            self::DUELS => TextFormat::WHITE,
            self::FACTIONS => TextFormat::LIGHT_PURPLE,
            self::MM => TextFormat::YELLOW,
            self::SB => TextFormat::DARK_AQUA,
            self::SW, self::SG, self::UHC => TextFormat::GOLD,
            self::TB => TextFormat::DARK_BLUE,
            default => TextFormat::AQUA,
        };
    }

    public function setSpawn(?Location $location): void
    {
        $this->spawnLocation = $location;
    }

    public function getSpawn(string $identifier = ''): Location
    {
        // Use the given spawn location that is set (In factions, the spawn location are different).
        if ($this->spawnLocation !== null) {
            return $this->spawnLocation;
        }

        $worldManager = $this->getPlugin()->getServer()->getWorldManager();
        /** @var World $defaultWorld */
        $defaultWorld = $worldManager->getDefaultWorld();

        $location = match ($this->getServerType()) {
            self::BW, self::MM => new Location(0.5, 65, 0.5, $defaultWorld, 0, 0),
            self::TB, self::DUELS => new Location(0.5, 60, 0.5, $defaultWorld, 0, 0),
            self::SW => new Location(128.5, 121, 128.5, $defaultWorld, 0, 0),
            self::FACTIONS => new Location(-13.5, 63, 10.5, $defaultWorld, 0, 0),
            self::SB => new Location(7.5, 99, -3.5, $defaultWorld, 315, 0),
            self::CREATIVE => new Location(0.5, 103, -16.5, $defaultWorld, 180, 0),
            self::MD => new Location(18.5, 60, 3.5, $defaultWorld, 45, 0),
            self::LOBBY, self::SETUP => match ($identifier) {
                self::AC => new Location(176, 79, 1438, $worldManager->getWorldByName('ArcadeLobby'), 180, 0),
                default => new Location(-26.5, 42, -22.5, $defaultWorld, 320, 0)
            },
            default => Location::fromObject($defaultWorld->getSpawnLocation()->add(0.5, 0, 0.5), $defaultWorld),
        };

        return Location::fromObject($location->add(mt_rand(-1, 1), 0, mt_rand(-1, 1)), $location->getWorld(), $location->getYaw(), $location->getPitch());
    }

    /**
     * @param NGPlayer $player
     * @param Cluster $cluster
     * @param bool $ignoreFull
     * @param Closure $onSuccess function(Server $server)
     * @param ?Closure $onFailure
     */
    public function findBestServer(NGPlayer $player, Cluster $cluster, bool $ignoreFull, Closure $onSuccess, ?Closure $onFailure = null): void
    {
        $player->sendMessage(TextFormat::RED . 'Matchmaking is currently unavailable.');
        if ($onFailure !== null) {
            $onFailure();
        }
    }

    public function getQueuingRegion(NGPlayer $player): string
    {
        $partyManager = $this->getPlugin()->getPlayerManager()->getSocialManager()->getPartyManager();

        if (($party = $partyManager->getParty($player)) === null) {
            return $player->getProxyRegion();
        }

        return $partyManager->getQueuingRegion($party);
    }

    public static function canJoinFullServers(Player $player): bool
    {
        return $player->hasPermission('nethergames.artist') || $player->hasPermission('nethergames.builder') || $player->hasPermission('nethergames.trainee') || $player->hasPermission('nethergames.vip.emerald');
    }

    public function getQueuingMode(NGPlayer $player): string
    {
        $plugin = $this->getPlugin();
        $partyManager = $plugin->getPlayerManager()->getSocialManager()->getPartyManager();
        $playerData = $plugin->getPlayerData();

        if (($party = $partyManager->getParty($player)) !== null) {
            return $partyManager->getQueuingMode($party);
        }

        $queuingMode = Minigame::QUEUING_GLOBAL;

        if ($playerData->getBool($player, PlayerData::ALLOW_TOUCH_QUEUEING)) {
            if ($playerData->getGameSettings()->getBool($player, GameSettings::TOUCH_ONLY)) {
                $queuingMode = Minigame::QUEUING_FORCE_MOBILE;
            } else {
                $queuingMode = Minigame::QUEUING_PREFER_MOBILE;
            }
        }

        return $queuingMode;
    }

    /**
     * @param Cluster[] $clusters
     */
    public function getStringStatus(array $clusters): string
    {
        $onlineServers = 0;
        $onlinePlayers = 0;

        foreach ($clusters as $cluster) {
            $onlineServers += $cluster->getStatus() === Server::ONLINE ? 1 : 0;
            $onlinePlayers += $cluster->getOnlinePlayers();
        }

        return $onlineServers > 0 ? CustomIcon::PLAYERS_TINY . TextFormat::GREEN . $onlinePlayers : TextFormat::RED . 'OFFLINE';
    }

    public function getGlobalPlayerCount(): int
    {
        $count = 0;

        foreach ($this->getClusters() as $cluster) {
            $count += $cluster->getOnlinePlayers();
        }

        return $count;
    }

    public function getServerRegion(): string
    {
        return explode('-', $this->getUniqueId())[0];
    }
}
