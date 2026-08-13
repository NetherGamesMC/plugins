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

namespace libminigames;

use libasyncio\FileCopyAsyncTask;
use libforms\elements\Button;
use libforms\FormManager;
use libminigames\events\MinigameJoinEvent;
use libminigames\events\MinigameQuitEvent;
use libminigames\events\MinigameStartEvent;
use libminigames\settings\EmptyGameSettings;
use libminigames\settings\GameSettings;
use libminigames\utils\ArenaConfig;
use libminigames\utils\Items;
use libminigames\utils\StatsData;
use libminigames\utils\Streaks;
use libminigames\utils\streaks\Streak;
use libminigames\utils\Utils;
use libReplay\session\record\Recording;
use libReplay\session\record\RecordManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\type\TextType;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\GameSettings as PlayerGameSettings;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\party\objects\Party;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\thread\NGThreadPool;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\scoreboard\Scoreboard;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_count_values;
use function array_diff;
use function array_filter;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_rand;
use function array_unique;
use function ceil;
use function count;
use function implode;
use function in_array;
use function max;
use function mt_rand;
use function number_format;
use function rand;
use function shuffle;
use function strtoupper;
use function strval;
use function time;

// [PHPStorm] CTRL + Q each phpdoc for better reading.
// [VSCode] Well goodluck.

/**
 * An abstract representation of an arena
 *
 * <p>Newly created arenas will have its own mode, you need to check for these modes in order
 * to work properly. {@link Arena} class provides its own player & spectator data, listener class
 * which are restricted (You have to enable a global listener {@link MinigameListener}), maps voting,
 * scoreboards and private games.
 *
 * <p>The concept of an arena can also have its own types which is specified by an interface class {@link TypeArena}.
 * For example, you would want to have 2 modes which is easy, medium, and insane, players can decide to vote
 * which type they would want to play. However, you will have to create your own voting forms.
 *
 * <p>Implementing listeners, the listeners object in this class is a non-listener interface. Which means that, all events
 * that is being called will be filtered in a global listener event {@link MinigameListener} and being passed to this arena
 * class {@link ArenaListener}, snippet below will explain on how to implement this:
 *
 * <code>
 *  public function __construct(...){
 *      parent::__construct(...);
 *
 *      // Make sure you call this method in constructor class.
 *      // But please take note that this is just an example, actual implementation
 *      // Of this method is to must override this class.
 *      $this->listener = new ArenaListener($this);
 *      ...
 *  }
 * </code>
 *
 * <p>For further understanding, I will link you some of the most used functions in this class
 * Try to make yourself useful by looking at these functions. Well, don't be so strict while using
 * this module, you can always try to override any functions that is not marked as final. This class
 * allows programmer to do your own things easily, try not to get in stuck while doing simple things.
 * <p>{@see Arena::bootMinigame()}
 * <p>{@see Arena::startGame()}
 * <p>{@see Arena::finishGame()}
 * <p>{@see Arena::getPlayers()}
 * <p>{@see Arena::setupMapFeatures()}
 * <p>{@see Arena::getMinimumPlayers()}
 * <p>{@see Arena::addParticipation()}
 *
 * @package libminigames
 */
abstract class Arena
{
    public const STATUS_WAITING = 0;    // Waiting lobby.
    public const STATUS_STARTING = 1;   // Waiting lobby but map is decided.
    public const STATUS_RUNNING = 2;    // Cages + Game running.
    public const STATUS_FINISHING = 3;  // Game has being finished.

    public const DATA_XP = 0;
    public const DATA_CREDITS = 1;
    public const DATA_COINS = 2;

    /** @var string[] */
    protected array $maps = [];
    /** @var ArenaListener */
    protected ArenaListener $listener;
    /** @var Player[] */
    protected array $queuedPlayers = [];
    /** @var World|null Returns null when the minigame hasn't been set up yet */
    protected ?World $world = null;
    /** @var string */
    protected string $mapName = '';
    /** @var StatsData|null */
    protected ?StatsData $statsData = null;
    /** @var Player[] */
    private array $players = [];
    /** @var Player[] */
    private array $spectators = [];
    /** @var Party|null */
    private ?Party $party = null;
    /** @var array<int, int> */
    private array $mapVotes = [];
    /** @var int */
    private int $xpBoost = 0;
    /** @var Scoreboard */
    private Scoreboard $scoreboard;
    /** @var int */
    private int $status = self::STATUS_WAITING;
    /** @var GameSettings */
    private GameSettings $settings;
    /** @var array<string, string> */
    private array $xuids = [];
    /** @var ?int */
    private ?int $replayId = null;
    /** @var int */
    private int $startTime;
    /** @var bool */
    private bool $partyGame = false;
    /** @var bool */
    private bool $startImmediately = false;


    /**
     * The {@link Arena} abstract constructor.
     *
     * <p>During initialization of an arena, this constructor will handle the game arena
     * and its matches, it will copy an arena world from plugin data directory <code>/arenas</code> into
     * server worlds directory as <code>Match-x-y</code> where x is the minigame name and y is the numerical <code>$id</code>.
     *
     * <p>After the world has successfully being copied, it will then call {@link Arena::bootMinigame()} where you
     * will have to register your own {@link CountDownTask} task.
     *
     * <p>Note to developers: Please do make sure that your worlds doesn't contains any periods on the
     * beginning of a filename, this will cause an issue upon deleting the world.
     *
     * @param Minigame $plugin Your minigame class.
     * @param int $modeId The mode of an arena.
     * @param int $id The id of an arena itself, this number must be different than others.
     * @param bool $privateGame Indicates if this arena is a private match.
     */
    public function __construct(
        private Minigame $plugin,
        private int      $modeId,
        private int      $id,
        private bool     $privateGame = false,
        ?GameSettings    $settings = null
    )
    {
        if ($this->getPlugin()->hasWaitingLobby()) {
            $worldName = $this->getWaitingLobbyWorldName();
            $saveName = 'WaitingLobby';
        } else {
            $worldName = $this->getMatchWorldName();
            $saveName = $this->getMapDisplayName();
        }

        NGThreadPool::getInstance()->submitTask(new FileCopyAsyncTask(Path::join($this->getPlugin()->getDataFolder(), $saveName), Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $worldName), function () use ($worldName) {
            $worldManager = $this->getPlugin()->getServer()->getWorldManager();
            $worldManager->loadWorld($worldName);
            $world = $worldManager->getWorldByName($worldName);

            if ($world !== null) {
                $world->setAutoSave(false);
                $world->setTime(World::TIME_DAY);
                $world->stopTime();
                $world->setChunkTickRadius(0);

                $this->world = $world;
                $this->bootMinigame();
            }
        }));

        $this->scoreboard = new Scoreboard(TextFormat::GOLD . TextFormat::BOLD . strtoupper($this->getPlugin()->getMinigameName()), SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, SetDisplayObjectivePacket::SORT_ORDER_DESCENDING);
        // If the settings passed is null, then we will use the skeleton class as default.
        $this->settings = $settings ?? new EmptyGameSettings();
    }

    public function getPlugin(): Minigame
    {
        return $this->plugin;
    }

    final public function getId(): int
    {
        return $this->id;
    }

    public function getMapDisplayName(bool $includeTag = false): string
    {
        return $this->getPlugin()->getMapDisplayName($this->mapName, $includeTag);
    }

    /**
     * Perform anything appropriate to the game after the world lobby/arena has successfully being copied
     * and being loaded. You must schedule an {@link CountDownTask} task here.
     */
    abstract public function bootMinigame(): void;

    /**
     * This method is used as a way to update the game settings of the world.
     * This method will be called when the creator of the private match has joined.
     */
    final public function registerGameSettings(Party $party): void
    {
        $this->party = $party;
        $leader = $party->getLeader();
        if ($leader === null) {
            return;
        }

        $ess = $this->getPlugin()->getEssentials();
        $serverManager = $ess->getServerManager();

        // This will attempt to override the settings to match that of the player's configured ones.
        $settings = $this->settings->fetchFromPlayer(
            player: $leader,
            serverType: $serverManager->getServerType(),
            gameType: $serverManager->getGameType()
        );
        // If the settings are null, then we will use the skeleton class as default.
        if ($settings === null) {
            return;
        }
        // Finally, we update the settings to match the player's.
        $this->settings = $settings;
    }

    public function playKillCosmetics(Player $player): void
    {
        if (!$player->isSpectator()) {
            CosmeticHandler::KILL_EFFECTS()->run($player, $location = $player->getLocation());
            CosmeticHandler::KILL_SOUNDS()->run($player, $location);
        }
    }

    /**
     * Send game analytics data to all the players in arena, this function will usually be called after a match
     * has completed. Put in some efforts while trying to write the statistics, or simply broadcast top deaths
     * for this match.
     */
    public function sendStats(): void
    {

    }

    /**
     * Attempts to push queued players from an array object {@link Arena::$queuedPlayers}. This is operation
     * is based on FIFO (First-In-First-Out) order which means, players who queued into this arena will always
     * be handled first.
     *
     * <h3>Program functions and its characteristics</h3>
     *
     * The function act as a **queue service for an arena**, a newly created arena will always be queued into this class.
     * Or simply put, each server instances running will always have 1 queue instance. This is to ensure the game which
     * is currently running or finished will not be queued into, which is handled by task {@link CountDownTask}.
     *
     * <p>The other function for this method is to **handle all inbound players** that has been queued to. You can override
     * this function in order to get these players before it is being queued into this arena. A programmer should always
     * call parent function {@link Arena::addQueuedPlayers()} after handling queued players.
     */
    public function addQueuedPlayers(): void
    {
        $queuedPlayers = [];

        foreach ($this->queuedPlayers as $id => $queuedPlayer) {
            if ($queuedPlayer->isConnected()) {
                if ($queuedPlayer->spawned) {
                    $queuedPlayers[] = $queuedPlayer;
                } else {
                    continue;
                }
            }
            unset($this->queuedPlayers[$id]);
        }

        if (count($queuedPlayers) > 0) {
            $plugin = $this->getPlugin();
            $scoreboard = $this->getScoreboard();
            $modeName = $plugin->getModeName($this->getModeId());
            $maxSize = $this->getMaxSize();
            $playerCount = count($this->getPlayers(false));

            $scoreboard->setLine($this->getPlayers(), 7, CustomIcon::PLAYERS_TINY . TextFormat::GREEN . $playerCount . '/' . $maxSize);

            foreach ($queuedPlayers as $player) {
                $scoreboard->addPlayer($player);
            }

            $scoreboard->setLines($queuedPlayers, [
                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                    2 => '',
                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $modeName,
                    4 => '',
                    5 => CustomIcon::HOURGLASS . match (true) {
                            $this->getGameSettings()->isPaused() => TextFormat::RED . "Paused",
                            $playerCount >= $this->getMinimumPlayers() => TextFormat::GREEN . 'Starting',
                            default => TextFormat::RED . "Waiting",
                        },
                    6 => '',
                    7 => CustomIcon::PLAYERS_TINY . TextFormat::GREEN . $playerCount . '/' . $maxSize,
                    8 => ''
                ]
            );
        }
    }

    public function getScoreboard(): Scoreboard
    {
        return $this->scoreboard;
    }

    final public function getModeId(): int
    {
        return $this->modeId;
    }

    /**
     * Returns a maximum player of a specified mode given, this function must be
     * a hardcoded value, to make it simple, every mode will have its own constant
     * maximum players so that arena config will no longer in need to be configured.
     *
     * You can use the getModeId() function to get the mode id.
     *
     * @return int
     */
    abstract public function getMaxSize(): int;

    /**
     * @param bool $includeSpectators
     * @return Player[]
     */
    public function getPlayers(bool $includeSpectators = true): array
    {
        if ($includeSpectators) {
            return array_unique(array_merge($this->players, $this->getSpectators()));
        }

        return $this->players;
    }

    /**
     * @return Player[]
     */
    final public function getSpectators(): array
    {
        return $this->spectators;
    }

    public function getWaitingLobbySpawn(): Location
    {
        $location = $this->getPlugin()->getEssentials()->getServerManager()->getSpawn();

        if ($this->world !== null) {
            $location->world = $this->world;
        }

        return $location;
    }

    /**
     * Attempts to add a player into this arena. This is an internal function and a programmer should not
     * override this function. You can however, attempt to override {@link Arena::addQueuedPlayers()} only if
     * you are not using {@link TeamArena} class, redirect to the function docs for further explanation.
     *
     * @param Player $player
     * @param bool $force
     * @return bool
     * @internal
     */
    final public function addPlayer(Player $player, bool $force = false): bool
    {
        $isPrivate = $this->isPrivateGame();

        $socialManager = $this->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager();
        if (!$force && ($party = $socialManager->getPartyManager()->getParty($player)) !== null) {
            $players = $socialManager->getPartyManager()->getPlayers($party);
            $partyMembers = array_diff($players, [$player]);

            foreach ($partyMembers as $p) {
                if ($this->getPlugin()->getEssentials()->getPlayerData()->getBool($p, PlayerData::TRACK)) {
                    $player->sendMessage(TextFormat::RED . $p->getName() . ' is tracking someone. Please wait for them to leave.');
                    return false;
                }
            }

            foreach ($partyMembers as $p) {
                if (($arena = $this->getPlugin()->getArena($p)) !== null) {
                    $arena->removePlayer($p, MinigameQuitEvent::PARTY);
                }

                $event = new MinigameJoinEvent($p, $this, $this->getModeId(), $this->getId());
                $event->call();

                if ($event->isCancelled()) {
                    return false;
                }
            }

            if (!$isPrivate && count($players) >= $this->getMaxSize()) {
                $this->partyGame = true;

                if ($player->hasPermission(Permissions::RANK_LEGEND)) {
                    $this->privateGame = true;
                    $player->sendMessage(TextFormat::GREEN . "Automatically created a private game.");
                }
            }

            if ($party->hasPlayerRandomization()) {
                shuffle($players);
            }

            $playerCount = count($players);
            if ($this instanceof TeamArena && $playerCount <= ($maxTeamSize = $this->getTeamSize()) && !$isPrivate) {
                if (!$this->isRunning() && $this->getPlugin()->balanceQueuing()) {
                    $bestTeamSize = $maxTeamSize;
                    $bestTeam = null;

                    foreach ($this->getTeams() as $team) {
                        $teamSize = $team->getSize();

                        if ($teamSize < $bestTeamSize && $maxTeamSize - $teamSize >= $playerCount) {
                            $bestTeamSize = $teamSize;
                            $bestTeam = $team;
                        }
                    }

                    if ($bestTeam !== null) {
                        foreach ($players as $p) {
                            $bestTeam->addPlayer($p);
                            $this->queuePlayer($p);
                        }

                        return true;
                    }
                } else {
                    foreach ($this->getTeams() as $team) {
                        if (count($team->getPlayers()) + $playerCount <= $maxTeamSize) {
                            foreach ($players as $p) {
                                $team->addPlayer($p);
                                $this->queuePlayer($p);
                            }
                            return true;
                        }
                    }
                }
            }

            if ($isPrivate) {
                $this->registerGameSettings($party);
            }

            foreach ($players as $p) {
                $this->addPlayer($p, true);
            }
        } else {
            if ($this instanceof TeamArena) {
                if ($this->getPlugin()->balanceQueuing()) {
                    $players = $this->getTeamSize();
                    $bestTeam = null;

                    foreach ($this->getTeams() as $team) {
                        if (($teamPlayers = count($team->getPlayers())) < $players) {
                            $players = $teamPlayers;
                            $bestTeam = $team;
                        }
                    }

                    /** @var Team $bestTeam */
                    $bestTeam->addPlayer($player);
                } else {
                    $team = null;
                    foreach ($this->getTeams() as $teamItem) {
                        if (count($teamItem->getPlayers()) < $this->getTeamSize()) {
                            $team = $teamItem;
                            break;
                        }
                    }

                    if ($team == null && $isPrivate) {
                        foreach ($this->getTeams() as $teamItem) {
                            if ($team == null || (count($teamItem->getPlayers()) < count($team->getPlayers()))) {
                                $team = $teamItem;
                            }
                        }
                    }

                    if ($team) {
                        $team->addPlayer($player);
                    } else {
                        $player->sendMessage(TextFormat::RED . "Something went wrong when adding you to that game. Please notify a developer of this issue.");
                        $this->getPlugin()->getEssentials()->getPlayerManager()->transferPlayer($player);
                    }
                }
            } else {
                $this->players[] = $player;
            }
            $this->queuePlayer($player);
        }

        return true;
    }

    /**
     * @param Player $player
     * @param int $reason
     * @param bool $force
     * @param bool $toHub
     * @return bool
     * @internal
     */
    final public function removePlayer(Player $player, int $reason, bool $force = false, bool $toHub = true): bool
    {
        $event = new MinigameQuitEvent($player, $this, $this->getModeId(), $this->getId(), $reason);
        $event->call();

        if (!$event->isCancelled()) {
            $ess = $this->getPlugin()->getEssentials();
            $playerManager = $ess->getPlayerManager();
            $partyManager = $playerManager->getSocialManager()->getPartyManager();
            $serverManager = $ess->getServerManager();

            if (!$force && ($reason === MinigameQuitEvent::END || $reason === MinigameQuitEvent::LEAVE) && ($party = $partyManager->getParty($player)) !== null) {
                if ($party->getLeaderName() === $player->getName()) {
                    $ingamePlayers = $this->isRunning() ? array_filter($partyManager->getPlayers($party), fn(Player $member) => !$this->isSpectator($member) && $member !== $player) : [];

                    if (count($ingamePlayers) !== 0) {
                        $form = FormManager::createModalForm($player);

                        if ($form !== null) {
                            $form->setTitle('Quit the match');

                            if (count($ingamePlayers) === 1) {
                                $form->setContent($playerManager->getPlayerName($ingamePlayers[array_key_first($ingamePlayers)]) . ' is still in the game. Are you sure you wish to take them to the lobby?');
                            } else {
                                $form->setContent(Utils::getPrettyList($playerManager->getPlayerNames($ingamePlayers)) . ' are still in the game. Are you sure you wish to take them to the lobby?');
                            }

                            $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GREEN . 'Yes', function (Player $player) use ($reason) {
                                $this->removePlayer($player, $reason, true);
                            }));
                            $form->setButton2(new Button(TextFormat::BOLD . TextFormat::RED . 'No'));

                            $form->sendForm();
                        }
                        return false;
                    }
                } else {
                    $player->sendMessage(TextFormat::RED . 'You can\'t go back to the lobby while you\'re in a party. Wait for your party host to decide when to return!');
                    return false;
                }
            }

            if ($this->isRunning() || $this->isFinishing()) {
                if (in_array($player, $this->getPlayers(false), true) && !$this->isPartyGame() && $reason !== MinigameQuitEvent::DISCONNECT_KICK) {
                    $this->addParticipation($player, [
                        self::DATA_XP => [],
                        self::DATA_CREDITS => [],
                        self::DATA_COINS => []
                    ]);
                }
                $this->despawnEntities($player);
            }
            $this->resetPlayer($player);

            if ($this->getPlugin()->getArenaConfig()->getTag($this->getMapName()) === ArenaConfig::TAG_HALLOWEEN) {
                $player->getEffects()->clear();
            }

            if ($this instanceof TeamArena) {
                if (($team = $this->getTeamNull($player)) !== null) {
                    $team->removePlayer($player);
                }
            } else {
                $this->getListener()->onArenaQuit($player);
            }

            $this->players = array_diff($this->players, [$player]);

            $this->getScoreboard()->removePlayer($player);

            $this->queuedPlayers = array_diff($this->queuedPlayers, [$player]);

            /** @var NGPlayer $player */
            $player->setEnergized(false);

            if ($ess->getServerManager()->enableCombatLogger()) {
                $ess->getCombatLogger()->wipeLog($player, true);
            }
            $this->getStatsData()->resetTempStats($player);

            if ($this->isSpectator($player)) {
                $this->spectators = array_diff($this->getSpectators(), [$player]);
            } elseif ($this->isRunning()) {
                if (isset($team)) {
                    $this->broadcastMessage($team->getPlayerName($player) . ' §7disconnected', true);
                } else {
                    $this->broadcastMessage($player->getDisplayName() . ' §7disconnected', true);
                }

                $player->sendMessage('§aYou left the match.');
            } elseif ($this->isWaiting() || $this->isStarting()) {
                unset($this->mapVotes[$player->getId()]);

                if (isset($team)) {
                    $this->broadcastMessage($team->getPlayerName($player) . ' §ehas quit!', true);
                } else {
                    $this->broadcastMessage($player->getDisplayName() . ' §ehas quit!', true);
                }
                $this->getScoreboard()->setLine($this->getPlayers(false), 7, CustomIcon::PLAYERS_TINY . TextFormat::GREEN . count($this->getPlayers()) . '/' . $this->getMaxSize());

                $this->xuids = array_diff($this->xuids, [$player->getXuid()]);
            }

            if (!$this->getPlugin()->isStandAloneGame() && $reason !== MinigameQuitEvent::DISCONNECT) {
                $player->setNameTag($playerManager->getNameTag($player, TextFormat::YELLOW));
            }

            if ($reason === MinigameQuitEvent::LEAVE || $reason === MinigameQuitEvent::END || $reason === MinigameQuitEvent::PARTY) {
                if (($party = $partyManager->getParty($player)) === null) {
                    if ($toHub && $this->getPlugin()->isStandAloneGame()) {
                        $playerManager->transferPlayer($player);
                    }
                } elseif ($party->getLeaderName() === $player->getName()) {
                    foreach ($party->getMembers() as $memberName) {
                        if (($member = $player->getServer()->getPlayerExact($memberName)) !== null) {
                            $reason = $this->isSpectator($member) ? MinigameQuitEvent::END : MinigameQuitEvent::PARTY;
                            $this->removePlayer($member, $reason, true);
                        }
                    }

                    if ($toHub && $this->getPlugin()->isStandAloneGame()) {
                        $playerManager->transferPlayer($player);
                    }
                }

                $player->teleport($serverManager->getSpawn());
            }

            if ($toHub && !$this->getPlugin()->isStandAloneGame()) {
                $player->setGamemode(GameMode::ADVENTURE);
            }

            return true;
        }
        return false;
    }

    public function isSpectator(Player $player): bool
    {
        return in_array($player, $this->getSpectators(), true);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isFinishing(): bool
    {
        return $this->status === self::STATUS_FINISHING;
    }

    public function isPrivateGame(): bool
    {
        return $this->privateGame;
    }

    /**
     * Returns whether a party is solely playing this game
     */
    public function isPartyGame(): bool
    {
        return $this->partyGame || $this->privateGame;
    }

    /**
     * Get if requested to start game immediately
     */
    public function shouldStartImmediately(): bool
    {
        return $this->startImmediately;
    }

    /**
     * Request to start game immediately
     */
    public function startImmediately(): void
    {
        $this->startImmediately = true;
    }

    /**
     * True whenever a game is played by only one player or team from the start
     */
    public function isOpponentlessGame(): bool
    {
        return $this->getInitialPlayerCount() === 1;
    }

    /**
     * @param array<self::DATA_*, array<array{string, int}>> $data
     */
    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        $ess = $this->getPlugin()->getEssentials();
        $playerData = $ess->getPlayerData();
        $playerManager = $ess->getPlayerManager();

        $crateKey = false;

        $totalXP = 0;
        $xpMultiplier = 1;
        $xpMultiplierReasons = [];

        $player->sendMessage("§e§lREWARD SUMMARY:");

        if (($kills = $this->getStatsData()->getValue($player, StatsData::KILLS)) !== 0) {
            $totalXP = ceil($kills / 2);
        }

        if ($won = $this->isWinner($player)) {
            $totalXP += 9;
            $crateKey = $playerManager->getCosmeticHandler()->shouldGiveCrateKey($player);

            if (($streaksKey = $this->getStreaksKey()) != null) {
                Streaks::Increment(
                    xuid: $player->getXuid(),
                    gameKey: $streaksKey,
                    onSelected: function (Streak $streak) use ($player) {
                        if (!$player->isConnected()) {
                            return;
                        }
                        if ($streak->isBestChanged()) {
                            $player->sendMessage("§l§6NEW BEST WIN STREAK: §r§b" . $streak->getCurrent());
                        } else {
                            $player->sendMessage("§6Your win streak is now §b" . $streak->getCurrent() . "§6 (best streak: §b" . $streak->getBest() . "§6)");
                        }
                    },
                    onError: function () use ($player) {
                        if (!$player->isConnected()) {
                            return;
                        }
                        $player->sendMessage("§cAn error occurred while updating your streak.");
                    }
                );
            }
        } else {
            if (($streaksKey = $this->getStreaksKey()) != null) {
                Streaks::Reset(
                    xuid: $player->getXuid(),
                    gameKey: $streaksKey,
                    onUpdated: function () use ($player) {
                        if (!$player->isConnected()) {
                            return;
                        }
                        $player->sendMessage("§cYou lost your streak!");
                    },
                    onError: function () use ($player) {
                        if (!$player->isConnected()) {
                            return;
                        }
                        $player->sendMessage("§cAn error occurred while updating your streak.");
                    });
            }
        }

        foreach ($data[self::DATA_XP] as $xpData) {
            [, $amount] = $xpData;

            if ($amount > 0) {
                $totalXP += $amount;
            }
        }

        if (\NetherGames\NGEssentials\utils\Utils::isWeekend()) {
            $xpMultiplierReasons[] = "the double XP weekend";
            $xpMultiplier *= 2;
        }

        if ($player->hasPermission(Permissions::TIER_DIAMOND)) {
            $xpMultiplierReasons[] = "your Diamond tier";
            $xpMultiplier *= 2;
        } else if ($player->hasPermission(Permissions::TIER_SAPPHIRE)) {
            $xpMultiplierReasons[] = "your Sapphire tier";
            $xpMultiplier *= 1.75;
        } else if ($player->hasPermission(Permissions::TIER_AMETHYST)) {
            $xpMultiplierReasons[] = "your Amethyst tier";
            $xpMultiplier *= 1.5;
        } else if ($player->hasPermission(Permissions::TIER_OPAL)) {
            $xpMultiplierReasons[] = "your Opal tier";
            $xpMultiplier *= 1.25;
        } else if ($player->hasPermission(Permissions::TIER_GOLD)) {
            $xpMultiplierReasons[] = "your Gold tier";
            $xpMultiplier *= 1.1;
        } else if ($player->hasPermission(Permissions::TIER_SILVER)) {
            $xpMultiplierReasons[] = "your Silver tier";
            $xpMultiplier *= 1.05;
        }

        switch ($this->getXpBoost()) {
            case 4:
                $xpMultiplierReasons[] = "a Titan player in-game";
                $xpMultiplier *= 3;
                break;
            case 3:
                $xpMultiplierReasons[] = "a Legend player in-game";
                $xpMultiplier *= 2.5;
                break;
            case 2:
                $xpMultiplierReasons[] = "an Emerald player in-game";
                $xpMultiplier *= 2;
                break;
            case 1:
                $xpMultiplierReasons[] = "an Ultra player in-game";
                $xpMultiplier *= 1.5;
                break;
        }

        if ((time() - $playerData->getInt($player, PlayerData::VOTE_TIME)) < (60 * 60 * 24)) {
            $xpMultiplierReasons[] = "your voting status";
            $xpMultiplier *= 1.25;
        }

        if ($this->getStatsData()->getValue($player, StatsData::PERFECT_SHOTS) > 0) {
            $totalXP += $this->getStatsData()->getValue($player, StatsData::PERFECT_SHOTS, true) * 3;
        }

        if ($this->isFinishing() || $this->isSpectator($player)) {
            $totalXP++;
        }

        if ($totalXP > 0) {
            $totalXP *= $xpMultiplier;

            $playerData->addInt($player, PlayerData::XP, (int)$totalXP);
            $player->sendMessage(CustomIcon::EXPERIENCE . '+' . (int)$totalXP . ' XP');
        }

        $creditsMultiplier = 1;
        $creditsMultiplierReasons = [];
        if (count($data[self::DATA_CREDITS]) !== 0) {
            $totalCredits = 0;

            foreach ($data[self::DATA_CREDITS] as $creditsData) {
                [, $amount] = $creditsData;

                if ($amount > 0) {
                    $totalCredits += $amount;
                }
            }

            if ((time() - $playerData->getInt($player, PlayerData::VOTE_TIME)) < (60 * 60)) {
                $creditsMultiplierReasons[] = "your voting status";
                $creditsMultiplier *= 2;
            }

            $totalCredits *= $creditsMultiplier;
            $playerData->addInt($player, PlayerData::STATUS_CREDITS, (int)$totalCredits);
            $player->sendMessage(CustomIcon::MYSTIC_CHEST . '+' . (int)$totalCredits . ' Credits');
        }

        if (count($data[self::DATA_COINS]) !== 0) {
            $totalCoins = 0;

            foreach ($data[self::DATA_COINS] as $coinsData) {
                [, $amount] = $coinsData;

                if ($amount > 0) {
                    $totalCoins += $amount;
                }
            }

            $playerData->addInt($player, PlayerData::COINS, (int)$totalCoins);
            $player->sendMessage(CustomIcon::COIN . '+' . (int)$totalCoins . ' Coins');
        }

        if ($won && $guildXP && ($guild = $playerManager->getSocialManager()->getGuildsManager()->getGuild($playerData->getInt($player, PlayerData::GUILD))) !== null) {
            if ($guild->isDisabled()) {
                $player->sendMessage(CustomIcon::SHIELD . ' ' . TextFormat::RED . "Guild Disabled");
            } else {
                $winXp = $this->getGXP();

                $player->sendMessage(CustomIcon::SHIELD . '+' . $winXp . " Guild XP");
                $guild->addXp($winXp);
            }
        }

        if ($crateKey) {
            $player->sendMessage(CustomIcon::KEY . '+1 Crate Key');
            $playerData->addInt($player, PlayerData::KEYS, 1);
        }

        if (count($xpMultiplierReasons) > 0) {
            $player->sendMessage(TextFormat::GREEN . "Your XP total includes " . (strval($xpMultiplier)[0] == "8" ? "an " : "a ") . TextFormat::BOLD . TextFormat::YELLOW . number_format($xpMultiplier, 2) . TextFormat::RESET . TextFormat::GREEN . " boost thanks to " . Utils::getPrettyList($xpMultiplierReasons));
        }
        if (count($creditsMultiplierReasons) > 0) {
            $player->sendMessage(TextFormat::GREEN . "Your credits total includes a " . TextFormat::BOLD . TextFormat::YELLOW . number_format($creditsMultiplier, 2) . TextFormat::RESET . TextFormat::GREEN . " boost thanks to " . Utils::getPrettyList($creditsMultiplierReasons));
        }
    }

    final public function getStatsData(): StatsData
    {
        if ($this->statsData === null) {
            throw new RuntimeException('StatsData is not registered');
        }

        return $this->statsData;
    }

    public function isWinner(Player $player): bool
    {
        return $this->getStatsData()->getValue($player, StatsData::WINS) > 0;
    }

    public function getXpBoost(): int
    {
        return $this->xpBoost ?? 0;
    }

    public function despawnEntities(Player $player): void
    {

    }

    public function resetPlayer(Player $player): void
    {
        $plugin = $this->getPlugin();
        $ess = $plugin->getEssentials();
        if ($ess->getServerManager()->enableCombatLogger()) {
            $ess->getCombatLogger()->wipeLog($player, true);
        }

        $player->getOffHandInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getCraftingGrid()->clearAll();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getXpManager()->setXpAndProgress(0, 0.0);

        if ($this->isRunning() || $this->isFinishing()) {
            $player->getEffects()->clear();
            $player->setHealth(20);

            if ($this->getPlugin()->getArenaConfig()->getTag($this->getMapName()) === ArenaConfig::TAG_HALLOWEEN) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 1, false));
            }
        }

        $player->extinguish();
    }

    public function getListener(): ArenaListener
    {
        return $this->listener;
    }

    /**
     * Broadcasts a message to all players in the arena.
     * @param string $message
     * @param bool $force If true, will send the message as a chat message. If false, will send the message as a conditional (chat/popup) message.
     * @param Player[] $excludePlayers Players to exclude from the broadcast.
     * @param int $type The medium in which you want to send a message (e.g. TYPE_TOAST, TYPE_TITLE, ect). Default is TYPE_ACTIONBAR.
     *
     * @return void
     * @see TextType for the different types of messages you can send.
     */
    public function broadcastMessage(string $message, bool $force = false, array $excludePlayers = [], int $type = TextType::TYPE_ACTIONBAR): void
    {
        foreach (array_diff($this->getPlayers(), $excludePlayers) as $player) {
            if ($force) {
                $player->sendMessage($message);
            } else {
                /** @var NGPlayer $player */
                $player->sendConditionalMessage($message, $type);
            }
        }
    }

    /**
     * This method returns the game settings associated with the arena.
     * Any gamemodes that want to implement custom settings should override
     * this method and return their own settings type.
     *
     * @return GameSettings
     */
    public function getGameSettings(): GameSettings
    {
        return $this->settings;
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isStarting(): bool
    {
        return $this->status === self::STATUS_STARTING;
    }

    final public function getSize(): int
    {
        return $this->getMaxSize() - count($this->getPlayers(false));
    }

    /**
     * In here should be all the prep work that gets done before the player is added to the waiting lobby
     *
     * @param Player $player
     */
    public function queuePlayer(Player $player): void
    {
        $ess = $this->getPlugin()->getEssentials();
        $playerManager = $ess->getPlayerManager();
        $enforcementHandler = $playerManager->getEnforcementHandler();

        if (!$this->isSpectator($player)) {
            $plugin = $this->getPlugin();

            if ($this->isPrivateGame()) {
                $player->sendMessage(TextFormat::GOLD . 'You are currently in a private party match. You will only be able to play with people in your party. Players outside of your party will not be able to join this match.');
            }

            /** @var NGPlayer $player */
            if ($this->isPartyGame()) {
                $player->sendMessage(TextFormat::RED . "This game will NOT impact your stats.");
            } elseif (mt_rand(1, 10) === 10) {
                if ($this->isTouchOnly()) {
                    $player->sendMessage(TextFormat::GOLD . 'You are currently in a touch controls only match - queuing might take slightly longer. You can play with all players (not only touch control users) by disabling "Touch only queuing" in Profile Settings -> Preferences while in the lobby.');
                } elseif ($player->getInputMode() === InputMode::TOUCHSCREEN) {
                    $player->sendMessage(TextFormat::GOLD . 'You are currently playing a match that includes players using any device. You can play matches with touchscreen players only by enabling "Touch only queuing" in Profile Settings -> Preferences while in the lobby.');
                }
            }

            $player->setGamemode(GameMode::ADVENTURE);
            $player->setEnergized();

            $playerManager->setStatsBar($player);

            $playerCount = count($this->getPlayers(false));
            $maxSize = $this->getMaxSize();
            if ($this instanceof TeamArena) {
                $team = $this->getTeam($player);

                $team->queuePlayer($player);

                $this->broadcastMessage($team->getPlayerName($player) . ' §ehas joined (§b' . $playerCount . '§e/§b' . $maxSize . '§e)!', true);

                if (!$this->isSoloGame()) {
                    $player->sendMessage('§eYou joined the ' . $team->getDisplayName() . ' §eteam');
                }
            } else {
                $this->xuids[$playerManager->getPlayerName($player)] = $player->getXuid();

                $inventory = $player->getInventory();
                $inventory->setHeldItemIndex(1);
                $inventory->setItem(Items::QUIT_BED, Items::getQuitBed());

                if ($this->isWaiting() && $plugin->hasWaitingLobby() && count($this->getMaps()) > 1) {
                    $inventory->setItem(Items::MAP_SELECTOR, Items::getMapSelectionPaper());
                }

                $player->setNameTag($player->getDisplayName());

                $this->broadcastMessage($player->getNameTag() . ' §ehas joined (§b' . $playerCount . '§e/§b' . $maxSize . '§e)!', true);
            }
        }

        if ($ess->getPlayerData()->getGameSettings()->getBool($player, PlayerGameSettings::ANNOUNCE_PLAYERS)) {
            if ($player->hasPermission(Permissions::RANK_VOTER)) {
                $oppTeamsArray = [];

                if ($this instanceof TeamArena) {
                    foreach ($this->getTeams() as $team) {
                        foreach ($team->getPlayers() as $oppPlayer) {
                            $oppTeamsArray[] = match ($oppPlayer === $player) {
                                true => $team->getColor() . TextFormat::BOLD . "You",
                                default => $team->getPlayerName($oppPlayer)
                            };
                        }
                    }
                } else {
                    foreach ($this->getPlayers(false) as $oppPlayer) {
                        if ($oppPlayer !== $player) {
                            $oppTeamsArray[] = $playerManager->getNameTag($oppPlayer);
                        }
                    }
                }

                $player->sendMessage(TextFormat::YELLOW . "In your game: " . implode(TextFormat::RESET . TextFormat::YELLOW . ", ", $oppTeamsArray));
            } else {
                $player->sendMessage("In your game: " . TextFormat::OBFUSCATED . TextFormat::RED . "OBFUSCATED");
                if (rand(1, 3) == 1) $player->sendMessage(TextFormat::AQUA . "Unlock this feature by voting at https://ngmc.co/v.");
            }
        }

        if ($this->isCreator($player)) {
            $player->getInventory()->setItem(Items::PRIVATE_GAME_SETTINGS, Items::getGameSettingsBlazeRod());
        }

        $player->teleport($this->getWaitingLobbySpawn());
        foreach ($enforcementHandler->isTracking($player->getName()) as $staffMember) {
            $staffMember->teleport($player->getLocation());
        }

        $this->queuedPlayers[] = $player;
    }

    public function isTouchOnly(): bool
    {
        $playerData = $this->getPlugin()->getEssentials()->getPlayerData();
        $mobileOnlyPlayers = 0;

        foreach ($this->getPlayers(false) as $player) {
            /** @var NGPlayer $player */
            if ($player->getInputMode() !== InputMode::TOUCHSCREEN) {
                return false;
            }

            if ($playerData->getGameSettings()->getBool($player, PlayerGameSettings::TOUCH_ONLY)) {
                $mobileOnlyPlayers++;
            }
        }

        return $mobileOnlyPlayers !== 0;
    }

    public function isSoloGame(): bool
    {
        return true;
    }

    /**
     * Returns all available maps that is configured in the game server.
     *
     * @return string[]
     */
    public function getMaps(): array
    {
        return $this->maps;
    }

    public function addSpectator(Player $player, bool $won = false): void
    {
        $this->spectators[] = $player;

        if (in_array($player, $this->getPlayers(false))) {
            $this->resetPlayer($player);
        }

        if ($won) {
            $player->setGamemode(GameMode::ADVENTURE);
        } else {
            $player->setGamemode(GameMode::SPECTATOR);
        }

        $player->getInventory()->setHeldItemIndex(1);
        if ($this->isFinishing()) {
            if ($this->getPlugin()->isStandAloneGame()) {
                $player->getInventory()->setContents([0 => Items::getReplayPaper(), 8 => Items::getQuitBed()]);
            } else {
                $player->getInventory()->setContents([8 => Items::getQuitBed()]);
            }
        } elseif ($this->getPlugin()->isStandAloneGame()) {
            $player->getInventory()->setContents([0 => Items::getReplayPaper(), 4 => Items::getSpectatorCompass(), 8 => Items::getQuitBed()]);
        } else {
            $player->getInventory()->setContents([4 => Items::getSpectatorCompass(), 8 => Items::getQuitBed()]);
        }
    }

    public function getMinimumPlayers(): int
    {
        return 4;
    }

    public function addMapVote(Player $player, int $mapId): void
    {
        $this->mapVotes[$player->getId()] = $mapId;
    }

    public function checkMapVotes(): void
    {
        $maps = $this->getMaps();

        if (count($maps) > 1) {
            $votes = array_count_values($this->mapVotes);
            if (count($votes) === 0) {
                $this->mapName = $maps[array_rand($maps)];
                $this->broadcastMessage(TextFormat::GOLD . $this->getMapDisplayName(true) . TextFormat::GREEN . ' has been randomly selected!', true);
            } else {
                $high_keys = array_keys($votes, max($votes));
                $mapId = $high_keys[array_rand($high_keys)];

                $this->mapName = $maps[$mapId];
                $this->broadcastMessage(TextFormat::GOLD . $this->getMapDisplayName(true) . TextFormat::GREEN . ' has won with ' . TextFormat::GOLD . $votes[$mapId] . TextFormat::GREEN . ' vote' . ((int)$votes[$mapId] > 1 ? 's' : '') . '!', true);

                foreach ($votes as $mapId => $voteCount) {
                    MySQLCredentials::executeChange("map_votes.add_votes", [
                        'game_mode' => $this->getPlugin()->getEssentials()->getServerManager()->getServerType(),
                        'map_name' => $maps[$mapId],
                        'votes' => $voteCount
                    ]);
                }
            }
        } else {
            $this->mapName = $maps[0];
        }
    }

    public function getMapVotes(int $mapId): int
    {
        $votes = array_count_values($this->mapVotes);

        return $votes[$mapId] ?? 0;
    }

    /**
     * Attempt to setup the map 5 seconds before the arena is starting.
     * This IO operation will be executed asynchronously. You can override this function
     * if your map is based on generators or etc.
     */
    public function setupMap(): void
    {
        $worldName = $this->getMatchWorldName();

        NGThreadPool::getInstance()->submitTask(new FileCopyAsyncTask(Path::join($this->getPlugin()->getDataFolder(), 'arenas', $this->getMapName()), Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $worldName), function () use ($worldName) {
            $worldManager = $this->getPlugin()->getServer()->getWorldManager();
            $worldManager->loadWorld($worldName);
            $world = $worldManager->getWorldByName($worldName);

            if ($world !== null) {
                $world->setTime($this->getPlugin()->getArenaConfig()->getTime($this));
                $world->stopTime();

                if (!NGEssentials::isInDevelopmentMode() && $this->getPlugin()->getReplaySystemStatus() && ($recordManager = RecordManager::getInstance()) !== null) {
                    $recordManager->startRecording($world, [
                        RecordManager::DATA_MAP_NAME => $this->getMapName(),
                        RecordManager::DATA_PLAYERS => $this->getPlugin()->getEssentials()->getPlayerManager()->getPlayerNames($this->getAlivePlayers(), true),
                        RecordManager::DATA_PRIVATE => $this->isPrivateGame(),
                        RecordManager::DATA_TOUCH_ONLY => $this->isTouchOnly(),
                    ], function (?Recording $recording): void {
                        if ($recording !== null) {
                            $this->replayId = $recording->getReplayId();
                        }
                    });
                }

                $this->setupMapFeatures($world);
            }
        }));
    }

    public function getReplayId(): ?int
    {
        return $this->replayId;
    }

    /**
     * @return int the time when the arena started
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getMapName(): string
    {
        return $this->mapName;
    }

    /**
     * @return Player[]
     */
    public function getAlivePlayers(): array
    {
        return array_diff($this->getPlayers(false), $this->getSpectators());
    }

    /**
     * Setup map features after the map has successfully being copied and loaded.
     * This function is followed right after the {@see Arena::setupMap()} when it has successfully being executed.
     *
     * @param World $world
     */
    public function setupMapFeatures(World $world): void
    {

    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function isFull(): bool
    {
        return $this->getSize() === 0;
    }

    /**
     * @return Player[]
     */
    public function getQueuedPlayers(): array
    {
        return $this->queuedPlayers;
    }

    public function broadcastTitle(string $title, string $subtitle = '', int $fadeIn = 0, int $stay = 40, int $fadeOut = 0): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
        }
    }

    public function broadcastTip(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendTip($message);
        }
    }

    public function broadcastPopup(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendPopup($message);
        }
    }

    public function getMatchWorldName(): string
    {
        return 'Match-' . $this->getPlugin()->getMinigameTag() . '-' . $this->getId();
    }

    public function getWaitingLobbyWorldName(): string
    {
        return 'Waiting-' . $this->getPlugin()->getMinigameTag() . '-' . $this->getId();
    }

    /**
     * @internal
     */
    public function start(): void
    {
        $alivePlayers = $this->getAlivePlayers();

        foreach ($alivePlayers as $player) {
            /** @var NGPlayer $player */
            $player->removeTitles();
            $player->resetTitles();
            $player->getInventory()->clearAll();
            $player->setEnergized(false);
            $player->extinguish();
            $player->getXpManager()->setXpAndProgress(0, 0.0);

            (new MinigameStartEvent($player, $this, $this->getModeId(), $this->getId()))->call();
        }

        if ($this->isPrivateGame()) {
            $this->getGameSettings()->sendSettingsAnnouncement($this);
        }

        $plugin = $this->getPlugin();
        if ($plugin->hasWaitingLobby()) {
            $world = $plugin->getServer()->getWorldManager()->getWorldByName($this->getMatchWorldName());

            if ($world === null) {
                $this->getPlugin()->getLogger()->error('Failed to start arena ' . $this->getId() . ' because the world ' . $this->getMatchWorldName() . ' does not exist');
                $this->finish();
                return;
            }

            $this->world = $world;

            $this->startGame();

            $plugin->removeWaitingLobby($this);
        } else {
            $this->startGame();
        }

        if ($this->isPrivateGame()) {
            $this->getGameSettings()->sendSettingsAnnouncement($this);
        }

        foreach ($this->getSpectators() as $spectator) {
            $spectator->setGamemode(GameMode::SPECTATOR);
            $spectator->teleport($this->getWorld()->getSpawnLocation());
        }

        $halloween = $plugin->getArenaConfig()->getTag($this->getMapName()) === ArenaConfig::TAG_HALLOWEEN;
        $effect = $halloween ? new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 1, false) : null;
        $enforcement = $plugin->getEssentials()->getPlayerManager()->getEnforcementHandler();
        foreach ($alivePlayers as $player) {
            foreach ($enforcement->isTracking($player->getName()) as $staffMember) {
                $staffMember->teleport($player->getLocation());
            }

            if ($effect !== null) {
                $player->getEffects()->add($effect);
            }
        }

        $this->calculateXpBoost();
        $this->setStatus(self::STATUS_RUNNING);
        $this->startTime = time();
    }

    /**
     * @internal
     */
    public function finish(): void
    {
        $this->setStatus(self::STATUS_FINISHING);

        $players = $this->getPlayers();

        foreach ($players as $player) {
            // If the player created the game, attempt to update their settings in the database
            if ($this->isCreator($player)) {
                $serverManager = NGEssentials::getInstance()->getServerManager();
                $this->getGameSettings()->saveToPlayer(
                    player: $player,
                    serverType: $serverManager->getServerType(),
                    gameType: $serverManager->getGameType()
                );
            }
            $this->removePlayer($player, MinigameQuitEvent::FINISH);
        }
        $this->requeuePlayers($players);

        $this->finishGame();

        if (!$this->isPrivateGame() && !ServerManager::$restarting) {
            $this->getStatsData()->save($this);
        }

        $this->getPlugin()->removeArena($this);
    }

    public function getWorld(): World
    {
        /** @var World $world */
        $world = $this->world;

        return $world;
    }

    /**
     * Returns true if set up as a private game + the player is the party creator
     *
     * @param Player $player
     * @return bool
     */
    public function isCreator(Player $player): bool
    {
        return $this->getParty()?->getLeader() === $player;
    }

    /**
     * Returns the party that created this arena when it's a private game.
     */
    public function getParty(): ?Party
    {
        return $this->party;
    }

    /**
     * @param Player[] $players
     */
    public function requeuePlayers(array $players): void
    {
        $plugin = $this->getPlugin();
        $ess = $plugin->getEssentials();
        $serverManager = $ess->getServerManager();
        $playerManager = $ess->getPlayerManager();
        $partyManager = $playerManager->getSocialManager()->getPartyManager();

        foreach ($players as $player) {
            /** @var NGPlayer $player */
            if ($partyManager->isInParty($player) && !$partyManager->isPartyCreator($player)) {
                continue;
            }

            if (in_array($plugin->getMinigameTag(), [ServerManager::MM, ServerManager::MS, ServerManager::SC], true)) {
                if ($plugin->isStandAloneGame()) {
                    $playerManager->transferPlayer($player);
                }
                continue;
            }

            $onMatchmakingFailure = static function () use ($plugin, $player): void {
                if ($player->isConnected()) {
                    $plugin->joinArena($player);
                }
            };

            if ($plugin->canJoinArena($player, $this->getModeId()) || !$playerManager->transferPlayer($player, $serverManager->getServerType(), $serverManager->getGameType(), true, $onMatchmakingFailure)) {
                $onMatchmakingFailure();
            }
        }
    }

    /**
     * Finishes off a game, the arena will no longer usable after this function has been executed.
     * You will need to set the arena status back to {@link Arena::STATUS_WAITING}.
     */
    public function finishGame(): void
    {

    }

    /**
     * All xuids of the players who are or were in the arena.
     * The key is the player name, and the value is the xuid.
     *
     * @return array<string, string>
     */
    public function getXuids(): array
    {
        return $this->xuids;
    }

    public function getInitialPlayerCount(): int
    {
        return count($this->xuids);
    }

    /**
     * The method that will be executed to start the game.
     *
     * <p>Before this method is being executed, an {@see Arena::setupMapFeatures()} will be called 5 seconds
     * before this function is getting executed. Within this seconds, the arena map will be copied into their
     * respective arena world name inside server worlds folder.
     *
     * <p>You also need to set scoreboard information here, this code below will explain how to do this without
     * having an NGEEssentials plugin:
     *
     * <code>
     *  // Here is how you set the lines for a player, take note that
     *  // first argument is an array of a player, and is in an descending order
     *  $this->getScoreboard()->setLines([$player], [
     *      9 => '',
     *      8 => 'Time Left: ' . TextFormat::GREEN . '5:00',
     *      7 => '',
     *      6 => 'Arena: ' . TextFormat::GREEN . $this->getMapName(),
     *      5 => 'Theme: ' . TextFormat::GREEN . $this->theme,
     *      4 => '',
     *      3 => 'Team color: ' . $aliveTeam->getColor() . $aliveTeam->getName(),
     *      2 => '',
     *      1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',]);
     *
     *  // The code below is how you set a specific line for a scoreboard.
     *  // The first argument are the same as above.
     *  $this->geScoreboard()->setLine([$player], 8, 'Time Left: ' . TextFormat::GREEN . '4:59',)
     * </code>
     */
    abstract public function startGame(): void;

    public function calculateXpBoost(): void
    {
        foreach ($this->getPlayers(false) as $player) {
            $this->xpBoost = max(
                $this->xpBoost,
                $player->hasPermission(Permissions::RANK_TITAN) ? 4 : 0,
                $player->hasPermission(Permissions::RANK_LEGEND) ? 3 : 0,
                $player->hasPermission(Permissions::RANK_EMERALD) ? 2 : 0,
                $player->hasPermission(Permissions::RANK_ULTRA) ? 1 : 0
            );

            if ($this->xpBoost === 4) {
                return;
            }
        }
    }

    public function isInArena(Player $player): bool
    {
        return in_array($player, $this->getPlayers(), true);
    }

    public function removeSpectator(Player $player): void
    {
        $this->spectators = array_diff($this->getSpectators(), [$player]);
    }

    /**
     * @return string|null
     * If this method returns null, streaks are *not* tracked for this game.
     */
    public function getStreaksKey(): ?string
    {
        return null;
    }

    public function getGXP(): int
    {
        return 10;
    }
}
