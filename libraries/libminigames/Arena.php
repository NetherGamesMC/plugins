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
use libminigames\events\arena\ArenaCreateEvent;
use libminigames\events\arena\ArenaEndEvent;
use libminigames\events\arena\ArenaStartEvent;
use libminigames\events\arena\ArenaStatusChangeEvent;
use libminigames\events\arena\MatchResult;
use libminigames\events\arena\PlayerMatchStats;
use libminigames\events\player\PlayerJoinEvent;
use libminigames\events\player\PlayerKillEvent;
use libminigames\events\player\PlayerMapVoteEvent;
use libminigames\events\player\PlayerQuitEvent;
use libminigames\events\player\PlayerRequeueEvent;
use libminigames\session\GameSession;
use libminigames\settings\EmptyGameSettings;
use libminigames\settings\GameSettings;
use libminigames\utils\ArenaConfig;
use libminigames\utils\Icon;
use libminigames\utils\Items;
use libminigames\utils\RewardEntry;
use libminigames\utils\scoreboard\Scoreboard;
use libminigames\utils\StatsData;
use libminigames\utils\TextType;
use libminigames\utils\TextUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
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
use function count;
use function implode;
use function in_array;
use function max;
use function mt_rand;
use function strtoupper;
use function time;

/**
 * An abstract representation of an arena.
 *
 * <p>Newly created arenas will have its own mode, you need to check for these modes in order
 * to work properly. {@link Arena} class provides its own player & spectator data, listening class
 * which are restricted (You have to enable a global listener {@link MinigameListener}), maps voting,
 * scoreboards and private games.
 *
 * <p>This class is engine-only: every NetherGames-specific decision (rewards, parties, economy,
 * matchmaking) is exposed through the events this class fires, and consumed outside of this library.
 *
 * <p>Implementing listeners, the listener object in this class is a non-listener interface. Which means that, all events
 * that is being called will be filtered in a global listener event {@link MinigameListener} and being passed to this arena
 * class {@link ArenaListener}, snippet below will explain on how to implement this:
 *
 * <code>
 *  public function __construct(...) {
 *      parent::__construct(...);
 *      $this->listener = new ArenaListener($this);
 *      ...
 *  }
 * </code>
 *
 * <p>{@see Arena::bootMinigame()}
 * <p>{@see Arena::startGame()}
 * <p>{@see Arena::finishGame()}
 * <p>{@see Arena::getPlayers()}
 * <p>{@see Arena::setupMapFeatures()}
 * <p>{@see Arena::getMinimumPlayers()}
 * <p>{@see Arena::getRewards()}
 *
 * @package libminigames
 */
abstract class Arena
{
    public const STATUS_WAITING = 0;    // Waiting lobby.
    public const STATUS_STARTING = 1;   // Waiting lobby but map is decided.
    public const STATUS_RUNNING = 2;    // Cages + Game running.
    public const STATUS_FINISHING = 3;  // Game has being finished.

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
    /** @var Player|null */
    private ?Player $creator = null;
    /** @var array<int, int> */
    private array $mapVotes = [];
    /** @var Scoreboard|null */
    private ?Scoreboard $scoreboard = null;
    /** @var int */
    private int $status = self::STATUS_WAITING;
    /** @var GameSettings */
    private GameSettings $settings;
    /** @var array<string, string> */
    private array $xuids = [];
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
     * server worlds directory as <code>Match-<tag>-<id></code>.
     *
     * <p>After the world has successfully being copied, it will then call {@link Arena::bootMinigame()} where you
     * will have to register your own {@link CountDownTask} task.
     *
     * @param Minigame $plugin Your minigame class.
     * @param int $modeId The mode of the arena.
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

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Path::join($this->getPlugin()->getDataFolder(), $saveName), Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $worldName), function () use ($worldName) {
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

        $this->scoreboard = $this->createScoreboard();
        // If the settings passed is null, then we will use the skeleton class as default.
        $this->settings = $settings ?? new EmptyGameSettings();

        (new ArenaCreateEvent($this))->call();
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
     * Perform anything appropriate to the game after the world lobby/arena has successfully been copied
     * and has been loaded. You must schedule an {@link CountDownTask} task here.
     */
    abstract public function bootMinigame(): void;

    public function playKillCosmetics(Player $player): void
    {
        // Kill cosmetics are handled externally by listening to {@see PlayerKillEvent}.
    }

    /**
     * Send game analytics data to all the players in arena, this function will usually be called after a match
     * has completed.
     */
    public function sendStats(): void
    {

    }

    /**
     * Attempts to push queued players from an array object {@link Arena::$queuedPlayers}. This operation
     * is based on FIFO (First-In-First-Out) order which means, players who queued into this arena will always
     * be handled first.
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
            if ($this->hasScoreboard()) {
                $scoreboard = $this->getScoreboard();

                foreach ($queuedPlayers as $player) {
                    $scoreboard->addPlayer($player);
                }
            }
        }
    }

    /**
     * Refreshes the waiting-lobby scoreboard for all players in this arena.
     *
     * <p>No-op when the scoreboard is disabled (see {@see Arena::createScoreboard()}). The full
     * line layout comes from {@see Arena::getWaitingScoreboardLines()}.
     *
     * @param int $countdown The current countdown value displayed by the waiting lobby.
     * @param bool $paused Whether the waiting lobby is currently paused.
     */
    public function refreshWaitingScoreboard(int $countdown, bool $paused): void
    {
        if (!$this->hasScoreboard()) {
            return;
        }

        $this->getScoreboard()->setLines($this->getPlayers(), $this->getWaitingScoreboardLines($countdown, $paused));
    }

    /**
     * Returns the full waiting-lobby scoreboard frame (rebuild the whole sidebar).
     *
     * <p>Override this method to fully customize the waiting-lobby scoreboard, including its footer,
     * icons, and row layout. The default uses generic descriptor glyphs via {@see Icon} (rendered as
     * empty strings when not registered) and a configurable footer from the <code>tagline</code> config
     * key (empty by default).
     *
     * @param int $countdown The current countdown value in seconds.
     * @param bool $paused Whether the waiting lobby is currently paused.
     * @return array<int, string>
     */
    protected function getWaitingScoreboardLines(int $countdown, bool $paused): array
    {
        $plugin = $this->getPlugin();
        $modeName = $plugin->getModeName($this->getModeId());
        $maxSize = $this->getMaxSize();
        $playerCount = count($this->getPlayers(false));

        $status = $paused ? TextFormat::RED . "Paused" : ($playerCount >= $this->getMinimumPlayers() ? TextFormat::GREEN . 'Starting' : TextFormat::RED . "Waiting");

        $tagline = $this->getPlugin()->getConfig()->get('tagline', '');
        $footer = Icon::get('footer', is_string($tagline) ? $tagline : '');

        return [
            1 => $footer !== '' ? TextFormat::GOLD . $footer : '',
            2 => '',
            3 => Icon::get('gamemode') . TextFormat::GREEN . $modeName,
            4 => '',
            5 => Icon::get('hourglass') . $status,
            6 => '',
            7 => Icon::get('players') . TextFormat::GREEN . $playerCount . '/' . $maxSize,
            8 => ''
        ];
    }

    /**
     * Returns the players in this arena that are currently alive (non-spectators).
     *
     * @return Player[]
     */
    public function getAlivePlayers(): array
    {
        return array_diff($this->getPlayers(false), $this->getSpectators());
    }

    /**
     * Creates the scoreboard this arena uses, or returns <code>null</code> to disable it entirely.
     *
     * <p>Override this method to change the title/display slot/sort order, return a custom
     * {@see Scoreboard} implementation, or disable the scoreboard by returning <code>null</code>.
     * The default builds the classic waiting-lobby board with the minigame name as the title.
     *
     * @return Scoreboard|null
     */
    protected function createScoreboard(): ?Scoreboard
    {
        return new Scoreboard(TextFormat::GOLD . TextFormat::BOLD . strtoupper($this->getPlugin()->getMinigameName()), SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, SetDisplayObjectivePacket::SORT_ORDER_DESCENDING);
    }

    /**
     * Returns whether this arena has a scoreboard enabled.
     *
     * <p>Always guards scoreboard access with this method before using {@see Arena::getScoreboard()}
     * when the arena is able to disable it (see {@see Arena::createScoreboard()}).
     *
     * @return bool
     */
    public function hasScoreboard(): bool
    {
        return $this->scoreboard !== null;
    }

    /**
     * Returns this arena's scoreboard.
     *
     * <p>Calling this method while the scoreboard is disabled (i.e. {@see Arena::createScoreboard()}
     * returned <code>null</code>) throws a {@see RuntimeException}. Guard calls with
     * {@see Arena::hasScoreboard()} when the arena may disable the board.
     *
     * @return Scoreboard
     * @throws RuntimeException if the scoreboard is disabled.
     */
    public function getScoreboard(): Scoreboard
    {
        if ($this->scoreboard === null) {
            throw new RuntimeException('Scoreboard is disabled for this arena');
        }

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
        $world = $this->world ?? $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();

        if ($world === null) {
            throw new RuntimeException('No world is available to serve as the waiting lobby.');
        }

        return Location::fromObject($world->getSafeSpawn(), $world);
    }

    /**
     * Attempts to add a player into this arena. This is an internal function and a programmer should not
     * override this function. You can however, attempt to override {@link Arena::addQueuedPlayers()} only if
     * you are not using {@link TeamArena} class.
     *
     * <p>Players that share the same group UUID (see {@see GameSession::getGroupUUID()}) are added as a single unit.
     *
     * @param Player $player
     * @param bool $force
     * @return bool
     * @internal
     */
    final public function addPlayer(Player $player, bool $force = false): bool
    {
        $isPrivate = $this->isPrivateGame();
        $players = [$player];

        if (!$force) {
            $groupUUID = GameSession::getSession($player)->getGroupUUID();

            if ($groupUUID !== null) {
                $groupedPlayers = [];

                foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
                    if (GameSession::getSession($p)->getGroupUUID()?->equals($groupUUID)) {
                        $groupedPlayers[] = $p;
                    }
                }

                if (count($groupedPlayers) > 0) {
                    $players = $groupedPlayers;
                }
            }

            foreach ($players as $p) {
                if (($otherArena = $this->getPlugin()->getArena($p)) !== null) {
                    $otherArena->removePlayer($p, PlayerQuitEvent::PARTY);
                }

                $event = new PlayerJoinEvent($p, $this, $this->getModeId());
                $event->call();

                if ($event->isCancelled()) {
                    return false;
                }
            }

            if (!$isPrivate && count($players) >= $this->getMaxSize()) {
                $this->partyGame = true;
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

            foreach ($players as $p) {
                $this->addPlayer($p, true);
            }

            return true;
        }

        if ($this instanceof TeamArena) {
            if ($this->getPlugin()->balanceQueuing()) {
                $size = $this->getTeamSize();
                $bestTeam = null;

                foreach ($this->getTeams() as $team) {
                    if (($teamPlayers = count($team->getPlayers())) < $size) {
                        $size = $teamPlayers;
                        $bestTeam = $team;
                    }
                }

                if ($bestTeam === null) {
                    $player->sendMessage(TextFormat::RED . "Something went wrong when adding you to that game. Please notify a developer of this issue.");
                    return false;
                }

                $bestTeam->addPlayer($player);
            } else {
                $team = null;
                foreach ($this->getTeams() as $teamItem) {
                    if (count($teamItem->getPlayers()) < $this->getTeamSize()) {
                        $team = $teamItem;
                        break;
                    }
                }

                if ($team === null && $isPrivate) {
                    foreach ($this->getTeams() as $teamItem) {
                        if ($team === null || (count($teamItem->getPlayers()) < count($team->getPlayers()))) {
                            $team = $teamItem;
                        }
                    }
                }

                if ($team !== null) {
                    $team->addPlayer($player);
                } else {
                    $player->sendMessage(TextFormat::RED . "Something went wrong when adding you to that game. Please notify a developer of this issue.");
                    return false;
                }
            }
        } else {
            $this->players[] = $player;
        }
        $this->queuePlayer($player);

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
        $event = new PlayerQuitEvent($player, $this, $reason);
        $event->call();

        if (!$event->isCancelled()) {
            if ($this->isRunning() || $this->isFinishing()) {
                $this->despawnEntities($player);
            }
            $this->resetPlayer($player);

            if ($this->getPlugin()->getArenaConfig()->getTag($this->getMapName()) === ArenaConfig::TAG_HALLOWEEN) {
                $player->getEffects()->clear();
            }

            $team = null;
            if ($this instanceof TeamArena) {
                if (($team = $this->getTeamNull($player)) !== null) {
                    $team->removePlayer($player);
                }
            } else {
                $this->getListener()->onArenaQuit($player);
            }

            $this->players = array_diff($this->players, [$player]);

            if ($this->hasScoreboard()) {
                $this->getScoreboard()->removePlayer($player);
            }

            $this->queuedPlayers = array_diff($this->queuedPlayers, [$player]);

            GameSession::getSession($player)->setEnergized(false);

            $this->getStatsData()->resetTempStats($player);

            if ($this->isSpectator($player)) {
                $this->spectators = array_diff($this->getSpectators(), [$player]);
            } elseif ($this->isRunning()) {
                if ($team !== null) {
                    $this->broadcastMessage($team->getPlayerName($player) . ' §7disconnected', true);
                } else {
                    $this->broadcastMessage($player->getDisplayName() . ' §7disconnected', true);
                }

                $player->sendMessage('§aYou left the match.');
            } elseif ($this->isWaiting() || $this->isStarting()) {
                unset($this->mapVotes[$player->getId()]);

                if ($team !== null) {
                    $this->broadcastMessage($team->getPlayerName($player) . ' §ehas quit!', true);
                } else {
                    $this->broadcastMessage($player->getDisplayName() . ' §ehas quit!', true);
                }
                if ($this->hasScoreboard()) {
                    $this->getScoreboard()->setLine($this->getPlayers(false), 7, Icon::get('players') . TextFormat::GREEN . count($this->getPlayers()) . '/' . $this->getMaxSize());
                }

                $this->xuids = array_diff($this->xuids, [$player->getXuid()]);
            }

            if (!$this->getPlugin()->isStandAloneGame() && $reason !== PlayerQuitEvent::DISCONNECT) {
                $player->setNameTag($player->getDisplayName());
            }

            if ($reason === PlayerQuitEvent::LEAVE || $reason === PlayerQuitEvent::END || $reason === PlayerQuitEvent::PARTY) {
                if ($toHub && $this->getPlugin()->isStandAloneGame()) {
                    $defaultWorld = $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();

                    if ($defaultWorld !== null) {
                        $player->teleport($defaultWorld->getSpawnLocation());
                    }
                }
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
     * Returns whether a group is solely playing this game.
     */
    public function isPartyGame(): bool
    {
        return $this->partyGame || $this->privateGame;
    }

    /**
     * Get if requested to start game immediately.
     */
    public function shouldStartImmediately(): bool
    {
        return $this->startImmediately;
    }

    /**
     * Request to start game immediately.
     */
    public function startImmediately(): void
    {
        $this->startImmediately = true;
    }

    /**
     * True whenever a game is played by only one player or team from the start.
     */
    public function isOpponentlessGame(): bool
    {
        return $this->getInitialPlayerCount() === 1;
    }

    /**
     * @deprecated Rewards are now produced via {@see Arena::getRewards()} and consumed externally through {@see PlayerQuitEvent}.
     *
     * @param array<array-key, mixed> $data
     */
    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        // Reward processing has moved out of libminigames; the original data-driven reward engine
        // is now run by an external module listening to PlayerQuitEvent / getRewards().
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

    /**
     * Builds the kill message for a player killed in this arena.
     *
     * <p>Fires a {@see PlayerKillEvent} so consumers (e.g. cosmetic/kill-message modules) can
     * replace the message, then returns {@see PlayerKillEvent::getKillMessage()}. Callers are
     * responsible for substituting the <code>{PLAYER}</code>/<code>{DAMAGER}</code> placeholders;
     * <code>$killer</code> is null for environment deaths (void/lava/fall) that have no damager.
     *
     * @param Player|null $killer
     * @param Player $victim
     * @param int $cause An {@link EntityDamageEvent} cause id.
     */
    public function getKillMessage(?Player $killer, Player $victim, int $cause): string
    {
        $event = new PlayerKillEvent($victim, $killer, $cause, '{PLAYER} §r§7died.');
        $event->call();

        return $event->getKillMessage();
    }

    /**
     * Computes the rewards a player earned for this match.
     *
     * <p>Specific minigames should override this method to report their distinct currencies.
     * The values are raw and unmodified; external modules (e.g. an economy system) are responsible
     * for mapping the {@see RewardEntry::$type} and applying their own multipliers/persistence.
     *
     * @param Player $player
     * @return RewardEntry[]
     */
    public function getRewards(Player $player): array
    {
        return [];
    }

    public function despawnEntities(Player $player): void
    {

    }

    public function resetPlayer(Player $player): void
    {
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
     * @param int $type The medium in which you want to send a message (e.g. TYPE_TOAST, TYPE_TITLE, ...). Default is TYPE_ACTIONBAR.
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
                $this->sendConditionalMessage($player, $message, $type);
            }
        }
    }

    private function sendConditionalMessage(Player $player, string $message, int $type = TextType::TYPE_ACTIONBAR): void
    {
        if (GameSession::getSession($player)->isPopupsEnabled() && $type !== TextType::TYPE_CHAT) {
            $centeredMessage = TextUtils::center($message);
            if ($type === TextType::TYPE_ACTIONBAR) {
                $player->sendActionBarMessage($centeredMessage);
            } elseif ($type === TextType::TYPE_POPUP) {
                $player->sendPopup($centeredMessage);
            } elseif ($type === TextType::TYPE_TIP) {
                $player->sendTip($centeredMessage);
            } elseif ($type === TextType::TYPE_JUKEBOX_POPUP) {
                $player->sendJukeboxPopup($centeredMessage);
            } elseif ($type === TextType::TYPE_TITLE) {
                $player->sendTitle($centeredMessage);
            } elseif ($type === TextType::TYPE_TOAST) {
                $player->sendToastNotification($centeredMessage, $message);
            } else {
                $player->sendMessage($message);
                $player->sendMessage(TextFormat::RED . "Error: Invalid message type: $type. Please report this to a staff member.");
            }
        } else {
            $player->sendMessage($message);
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
     * Performs all the prep work that needs to be done before the player is added to the waiting lobby.
     *
     * @param Player $player
     */
    public function queuePlayer(Player $player): void
    {
        if (!$this->isSpectator($player)) {
            $plugin = $this->getPlugin();
            $session = GameSession::getSession($player);

            if ($this->isPrivateGame()) {
                $player->sendMessage(TextFormat::GOLD . 'You are currently in a private party match. You will only be able to play with people in your party. Players outside of your party will not be able to join this match.');
            }

            if ($this->isPartyGame()) {
                $player->sendMessage(TextFormat::RED . "This game will NOT impact your stats.");
            } elseif (mt_rand(1, 10) === 10) {
                if ($this->isTouchOnly()) {
                    $player->sendMessage(TextFormat::GOLD . 'You are currently in a touch controls only match - queuing might take slightly longer. You can play with all players (not only touch control users) by disabling "Touch only queuing" in Profile Settings -> Preferences while in the lobby.');
                } elseif ($session->getInputMode() === InputMode::TOUCHSCREEN) {
                    $player->sendMessage(TextFormat::GOLD . 'You are currently playing a match that includes players using any device. You can play matches with touchscreen players only by enabling "Touch only queuing" in Profile Settings -> Preferences while in the lobby.');
                }
            }

            $player->setGamemode(GameMode::ADVENTURE);
            $session->setEnergized();

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
                $this->xuids[$player->getName()] = $player->getXuid();

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

        if ($this->isCreator($player)) {
            $player->getInventory()->setItem(Items::PRIVATE_GAME_SETTINGS, Items::getGameSettingsBlazeRod());
        }

        $player->teleport($this->getWaitingLobbySpawn());

        $this->queuedPlayers[] = $player;
    }

    public function isTouchOnly(): bool
    {
        $mobileOnlyPlayers = 0;

        foreach ($this->getPlayers(false) as $player) {
            $session = GameSession::getSession($player);

            if ($session->getInputMode() !== InputMode::TOUCHSCREEN) {
                return false;
            }

            if ($session->isTouchOnlyPreference()) {
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
        $event = new PlayerMapVoteEvent($player, $this, $mapId);
        $event->call();

        if ($event->isCancelled()) {
            return;
        }

        $this->mapVotes[$player->getId()] = $event->getMapId();
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

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Path::join($this->getPlugin()->getDataFolder(), 'arenas', $this->getMapName()), Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $worldName), function () use ($worldName) {
            $worldManager = $this->getPlugin()->getServer()->getWorldManager();
            $worldManager->loadWorld($worldName);
            $world = $worldManager->getWorldByName($worldName);

            if ($world !== null) {
                $world->setTime($this->getPlugin()->getArenaConfig()->getTime($this));
                $world->stopTime();

                $this->setupMapFeatures($world);
            }
        }));
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
        $oldStatus = $this->status;
        $this->status = $status;

        (new ArenaStatusChangeEvent($this, $oldStatus, $status))->call();
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
            $player->removeTitles();
            $player->resetTitles();
            $player->getInventory()->clearAll();
            GameSession::getSession($player)->setEnergized(false);
            $player->extinguish();
            $player->getXpManager()->setXpAndProgress(0, 0.0);
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
        }

        // Fired with the match world already resolved (see {@see Arena::getWorld()}).
        (new ArenaStartEvent($this))->call();

        if ($this->isPrivateGame()) {
            $this->getGameSettings()->sendSettingsAnnouncement($this);
        }

        $this->startGame();

        if ($plugin->hasWaitingLobby()) {
            $plugin->removeWaitingLobby($this);
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
        foreach ($alivePlayers as $player) {
            if ($effect !== null) {
                $player->getEffects()->add($effect);
            }
        }

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

        $playerStats = [];
        foreach ($players as $player) {
            $playerStats[] = new PlayerMatchStats(
                player: $player,
                winner: $this->isWinner($player),
                stats: $this->getStatsData()->snapshot($player),
                extra: []
            );
        }

        (new ArenaEndEvent($this, new MatchResult($playerStats)))->call();

        foreach ($players as $player) {
            $this->removePlayer($player, PlayerQuitEvent::FINISH);
        }
        $this->requeuePlayers($players);

        $this->finishGame();

        $this->getPlugin()->removeArena($this);
    }

    public function getWorld(): World
    {
        /** @var World $world */
        $world = $this->world;

        return $world;
    }

    /**
     * Returns true if set up as a private game + the player is the game's creator.
     *
     * @param Player $player
     */
    public function isCreator(Player $player): bool
    {
        return $this->creator === $player;
    }

    /**
     * Marks the player who created this arena (for private games).
     *
     * @deprecated The concept of a private game creator is being moved into the engine via group UUIDs.
     */
    final public function setCreator(?Player $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * Converts this arena into a private game owned by the given creator, or back into a public one.
     *
     * <p>Group owners (e.g. a party with private-games enabled) use this to seal an arena for their
     * group only. Passing <code>null</code> clears both the private flag and the creator.
     *
     * @param bool $private
     * @param Player|null $creator
     */
    public function setPrivate(bool $private, ?Player $creator): void
    {
        $this->privateGame = $private;
        if (!$private) {
            $this->creator = null;

            return;
        }

        $this->creator = $creator;
    }

    /**
     * @param Player[] $players
     * @param int $size The authoritative amount of players requeueing together (e.g. a party).
     */
    public function requeuePlayers(array $players, int $size = 1): void
    {
        $plugin = $this->getPlugin();
        $mode = $plugin->getModes()[$this->getModeId()] ?? '';

        foreach ($players as $player) {
            $event = new PlayerRequeueEvent($player, $this, $mode, $size);
            $event->call();

            if (!$event->isCancelled()) {
                $plugin->joinArena($player, $this->getModeId(), $event->getSize());
            }
        }
    }

    /**
     * Finishes off a game, the arena will no longer usable after this function has been executed.
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
     * <p>You also need to set scoreboard information here.
     */
    abstract public function startGame(): void;

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
