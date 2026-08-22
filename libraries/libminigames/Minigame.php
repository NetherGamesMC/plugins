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

use libasyncio\FileDeleteAsyncTask;
use libminigames\commands\RequeueCommand;
use libminigames\events\arena\ArenaCleanupEvent;
use libminigames\events\player\PlayerCreatePrivateGameEvent;
use libminigames\events\player\PlayerJoinEvent;
use libminigames\events\player\PlayerQuitEvent;
use libminigames\events\player\PlayerRequeueEvent;
use libminigames\session\GameSession;
use libminigames\session\GameSessionListener;
use libminigames\utils\ArenaConfig;
use libminigames\utils\Icon;
use libminigames\utils\generators\VoidGenerator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_filter;
use function array_key_first;
use function array_key_last;
use function array_merge;
use function array_search;
use function count;
use function explode;
use function glob;
use function in_array;
use function max;
use function str_replace;
use function strtolower;
use const GLOB_ONLYDIR;

/**
 * A generic minigame engine that can be extended by any minigame plugin.
 *
 * <p>This class deliberately holds no NetherGames-specific behavior. Decisions that used to be
 * handled internally (rewards, parties, matchmaking, economy, ...) are now exposed as events which
 * any external module can subscribe to.
 *
 * @package libminigames
 */
abstract class Minigame extends PluginBase
{
    public const QUEUING_GLOBAL = 'GLOBAL';
    public const QUEUING_PREFER_MOBILE = 'PREFERRED_TOUCH_ONLY';
    public const QUEUING_FORCE_MOBILE = 'FORCED_TOUCH_ONLY';

    protected const REPLAY_SYSTEM_STATUS_ON = true;
    protected const REPLAY_SYSTEM_STATUS_OFF = false;

    /** @var self */
    private static Minigame $instance;

    /** @var Arena[] */
    protected array $arenas = [];
    /** @var int */
    protected int $mapsPlayed = 0; // Increment this value every new arenas being constructed.
    /** @var bool */
    protected bool $replaySystemStatus = self::REPLAY_SYSTEM_STATUS_ON;

    /**
     * Returns NG replay status system, if you want to disable this feature, please do so
     * by changing the value {@link Minigame::$replaySystemStatus} to {@link Minigame::REPLAY_SYSTEM_STATUS_OFF}.
     *
     * @return bool
     */
    final public function getReplaySystemStatus(): bool
    {
        return $this->replaySystemStatus;
    }

    /**
     * Returns the name of a specified mode given.
     * The modes registered can be found in {@link Minigame::getModes()}
     *
     * @param int $mode
     * @return string
     */
    final public function getModeName(int $mode): string
    {
        return $this->getModes()[$mode] ?? '';
    }

    /**
     * Specifies the available modes for this game.
     *
     * <p>This snippet below will show you how to properly override this method.
     * <code>
     *    public function getModes(): array {
     *        // 0 -> 'Normal', 1 -> 'Solo', 2 -> 'Doubles'
     *        return [
     *            'Normal',
     *            'Solo',
     *            'Doubles'
     *        ];
     *    }
     * </code>
     *
     * <p>String values returned are used in scoreboards.
     *
     * @return string[]
     */
    abstract public function getModes(): array;

    /**
     * Handles initialization of a minigame. Do not override this function
     * as it will prepare to clean arenas worlds due to segmentation faults or
     * crashes.
     *
     * <p>Until it has successfully being enabled, it will then call {@link Minigame::registerClasses()}
     * where you can register your arena listeners and config where appropriate.
     */
    public function onEnable(): void
    {
        self::$instance = $this;

        $matches = glob(Path::join($this->getServer()->getDataPath(), 'worlds', 'Match-*'), GLOB_ONLYDIR);

        if ($matches !== false) {
            foreach ($matches as $match) {
                try {
                    Filesystem::recursiveUnlink($match);
                } catch (RuntimeException $exception) {

                }
            }
        }

        if ($this->enableVoidGenerator()) {
            /** @var GeneratorManager $generatorManager */
            $generatorManager = GeneratorManager::getInstance();
            $generatorManager->addGenerator(VoidGenerator::class, 'flat', fn() => null, true);
            $generatorManager->addGenerator(VoidGenerator::class, 'void', fn() => null, true);
        }

        if ($this->hasWaitingLobby()) {
            $waitingLobbies = glob(Path::join($this->getServer()->getDataPath(), 'worlds', 'Waiting-*'), GLOB_ONLYDIR);

            if ($waitingLobbies !== false) {
                foreach ($waitingLobbies as $waitingLobby) {
                    try {
                        Filesystem::recursiveUnlink($waitingLobby);
                    } catch (RuntimeException $exception) {

                    }
                }
            }
        }

        if (!is_dir($this->getDataFolder())) {
            if (!mkdir($concurrentDirectory = $this->getDataFolder()) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            if (!mkdir($concurrentDirectory = $this->getDataFolder() . 'arenas/', 0755) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        $this->registerClasses();

        $this->getServer()->getPluginManager()->registerEvents(new GameSessionListener(), $this);
        $this->getServer()->getCommandMap()->register('requeue', new RequeueCommand($this));

        $this->getLogger()->info('§2' . $this->getDescription()->getName() . ' successfully enabled!');
    }

    public function enableVoidGenerator(): bool
    {
        return true;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * Indicate that this game has a waiting lobby, you can override this
     * function if it is appropriate with what you need.
     *
     * <p>If this feature were enabled, you will need to have a <code>WaitingLobby</code>
     * map under your plugin data folder in order to work properly.
     *
     * @return bool
     */
    public function hasWaitingLobby(): bool
    {
        return true;
    }

    /**
     * Initializes methods that are needed such as listeners, config files or anything
     * appropriate, just like how you handle {@see PluginBase::onEnable()}.
     */
    abstract public function registerClasses(): void;

    public function getMapDisplayName(string $mapName, bool $includeTag = false): string
    {
        $e = explode('-', $mapName);
        $mapDisplayName = str_replace('_', ' ', $e[array_key_last($e)]);

        if ($includeTag) {
            $mapTag = $this->getArenaConfig()->getTag($mapName);

            return $mapDisplayName . match ($mapTag) {
                    ArenaConfig::TAG_WINTER => ' ' . Icon::get('tag.winter'),
                    ArenaConfig::TAG_HALLOWEEN => ' ' . Icon::get('tag.halloween'),
                    ArenaConfig::TAG_NEW => ' ' . Icon::get('tag.new'),
                    ArenaConfig::TAG_SUMMER => ' ' . Icon::get('tag.summer'),
                    default => ''
                };
        }

        return $mapDisplayName;
    }

    /**
     * Returns mini-game's own arena config.
     * It must be extending {@link ArenaConfig}
     *
     * @return ArenaConfig
     */
    abstract public function getArenaConfig(): ArenaConfig;

    /**
     * Attempts to join a player to an available arena.
     *
     * <p>The engine does not compute group sizes on its own. Callers that know the number of
     * players queueing together (e.g. a party) must pass it explicitly in <code>$size</code>;
     * standalone single-player queues use the default of <code>1</code>.
     *
     * @param Player $player The player requested to join this arena.
     * @param int $modeId The mode of the arena requested, as seen in {@link Minigame::getModes()}.
     * @param int $size The authoritative amount of players that are queuing together (e.g. a party).
     * @return bool The value indicates that the player has successfully joined to their requested arena.
     */
    final public function joinArena(Player $player, int $modeId = -1, int $size = 1): bool
    {
        if ($this->getArena($player) !== null) {
            $player->sendMessage('§cYou\'re already in a ' . $this->getMinigameName() . ' match!');
            return false;
        }

        if ($modeId === -1) {
            $key = array_key_first($this->getModes());

            if ($key === null) {
                $player->sendMessage(TextFormat::RED . "Something went wrong.");
                return false;
            }

            /** @var int $modeId */
            $modeId = (int)$key;
        }

        $arena = $this->getFreeArena($modeId, $size);

        if ($arena === null) {
            $player->sendMessage(TextFormat::RED . "Could not join that game right now.");
            return false;
        }

        $event = new PlayerJoinEvent($player, $arena, $modeId);
        $event->call();

        if (!$event->isCancelled()) {
            return $arena->addPlayer($player);
        }

        return false;
    }

    /**
     * self-explanatory.
     *
     * @param Player $player The player
     * @return Arena|null
     */
    public function getArena(Player $player): ?Arena
    {
        foreach ($this->getArenas(-1, true) as $arena) {
            if ($arena->isInArena($player)) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * Returns a set of arenas that is available and exists, this function filters out any private
     * games that is created from a player if it is specified.
     *
     * @param int $modeId Required mode that for a game.
     * @param bool $includePartyGames Includes party games.
     * @return Arena[]
     */
    public function getArenas(int $modeId = -1, bool $includePartyGames = false): array
    {
        if ($modeId === -1) {
            return $this->arenas;
        }

        return array_filter($this->arenas, static function (Arena $arena) use ($modeId, $includePartyGames): bool {
            return $arena->getModeId() === $modeId && ($includePartyGames || !$arena->isPartyGame());
        });
    }

    /**
     * @param string $modeName
     * @return int A mode from a given string inside {@link Minigame::getModes()}.
     */
    final public function getModeId(string $modeName): int
    {
        return (int)array_search($modeName, $this->getModes(), true);
    }

    /**
     * The full display name of the game. This is configurable via the <code>name</code>
     * key in the plugin config.
     *
     * @return string
     */
    final public function getMinigameName(): string
    {
        $name = $this->getConfig()->get('name', '');

        return is_string($name) && $name !== '' ? $name : $this->getDescription()->getName();
    }

    /**
     * The name of the game, full name if it is possible. This name will be used
     * for your command label and arena world filename. This is read from the <code>tag</code>
     * key in the plugin config.
     *
     * @return string
     */
    public function getMinigameTag(): string
    {
        $tag = $this->getConfig()->get('tag', '');

        return is_string($tag) && $tag !== '' ? strtolower($tag) : strtolower($this->getDescription()->getName());
    }

    /**
     * Requeues a player into the same game mode they just played.
     *
     * @param Player $player The player to requeue
     * @param Arena $arena The arena the player is currently in
     * @param string $mode The game mode to requeue into
     * @param int $size The amount of players that are requeueing together (e.g. a party).
     */
    final public function requeuePlayer(Player $player, Arena $arena, string $mode, int $size = 1): void
    {
        $arena->removePlayer($player, PlayerQuitEvent::END, true, false);

        $event = new PlayerRequeueEvent($player, $arena, $mode, $size);
        $event->call();

        if (!$event->isCancelled()) {
            $this->joinArena($player, $this->getModeId($mode), $event->getSize());
        }
    }

    /**
     * Creates a new destructible arenas, instances created binds its id to their respective
     * arena world, in which the id were set by {@see Arena::getId()} and named <code>Match-x</code>,
     * increment this value with a given protected variable, {@see Minigame::$mapsPlayed}.
     * An arena can have its own private game if it is set.
     *
     * <p><em>This method will be executed internally, attempt not to execute this
     * function.</em>
     *
     * @param int $modeId The mode of the arena which will be used for the game.
     * @param bool $privateGame Enables private game.
     * @return Arena
     */
    abstract public function generateNewArena(int $modeId, bool $privateGame = false): Arena;

    /**
     * Get an available arena based on the given size, this call will attempt to iterate all
     * possible arenas that are waiting. Only the first result of an arena will be returned.
     * However, if there is no possible arena is available, a new arena will be created.
     *
     * @param int $modeId The specific mode set in {@link Minigame::getModes()}
     * @param int $size The size of a player group that wants to join.
     * @return Arena|null Returns null when the size exceeds the maximum size of an arena.
     */
    public function getFreeArena(int $modeId, int $size): ?Arena
    {
        $bestArena = null;
        $playerCount = 0;

        foreach ($this->getQueuingArenas($modeId) as $arena) {
            if ($arena->getSize() >= $size && $playerCount <= ($arenaCount = count($arena->getPlayers(false)))) {
                $bestArena = $arena;
                $playerCount = $arenaCount;
            }
        }

        if ($bestArena === null) {
            $arena = $this->generateNewArena($modeId);

            if ($size > $arena->getMaxSize()) {
                return null;
            }

            $this->arenas[$arena->getId()] = $arena;

            return $arena;
        }

        return $bestArena;
    }

    /**
     * Queries an arena that is ready to receive queued players.
     *
     * @param int $modeId The specific mode of a game, {@see Minigame::getModes()}
     * @return Arena[]
     */
    public function getQueuingArenas(int $modeId): array
    {
        $queuingArenas = [];

        foreach ($this->getArenas($modeId) as $arena) {
            if (!$arena->isFull() && $arena->isWaiting()) {
                $queuingArenas[] = $arena;
            }
        }

        return $queuingArenas;
    }

    /**
     * Allow hub queuing for the match, hub queuing allows players to teleport
     * to the server's lobby after the match has finished/completed.
     *
     * <p>This value is read from the <code>standalone</code> key in the plugin's config,
     * defaulting to <code>true</code>.
     *
     * @return bool
     */
    public function isStandAloneGame(): bool
    {
        return (bool)$this->getConfig()->get('standalone', true);
    }

    /**
     * Returns the first arena that had or has someone with that xuid playing.
     *
     * @param string $xuid
     * @return Arena|null
     */
    final public function getArenaByXuid(string $xuid): ?Arena
    {
        foreach ($this->getArenas(-1, true) as $arena) {
            if (in_array($xuid, $arena->getXuids())) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * Queues the teams equal to each other. Only works for Teams with an TeamArena
     *
     * @return bool
     */
    public function balanceQueuing(): bool
    {
        return false;
    }

    public function canJoinArena(int $size, int $modeId): bool
    {
        foreach ($this->getQueuingArenas($modeId) as $arena) {
            if ($arena->getSize() >= $size) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finalizes the arena cycles and destruct them including their runtime arena worlds.
     * As seen in this code, <code>Match-x</code> world will be deleted.
     *
     * <p>Under certain circumstances (Such as crashes or segmentation faults), {@link Minigame::onEnable()}
     * will try to delete recently created arenas and finally initialize.
     *
     * @param Arena $arena
     */
    final public function removeArena(Arena $arena): void
    {
        unset($this->arenas[$arena->getId()]);

        if ($this->hasWaitingLobby()) {
            if (!$arena->isRunning() && !$arena->isFinishing()) {
                $this->removeWaitingLobby($arena);
            }

            if ($arena->isWaiting()) {
                return;
            }
        }

        (new ArenaCleanupEvent($arena))->call();

        $worldManager = $this->getServer()->getWorldManager();

        if (($world = $worldManager->getWorldByName($worldName = $arena->getMatchWorldName())) !== null) {
            $world->setAutoSave(false);
            $worldManager->unloadWorld($world);
        }

        Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(Path::join($this->getServer()->getDataPath(), 'worlds', $worldName)));
    }

    final public function removeWaitingLobby(Arena $arena): void
    {
        $worldManager = $this->getServer()->getWorldManager();

        if (($world = $worldManager->getWorldByName($worldName = $arena->getWaitingLobbyWorldName())) !== null) {
            $worldManager->unloadWorld($world);
        }

        Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(Path::join($this->getServer()->getDataPath(), 'worlds', $worldName)));
    }

    /**
     * Creates a new private arena for the given creator and registers it into the engine.
     *
     * <p>Fires a cancellable {@see PlayerCreatePrivateGameEvent} first; when cancelled the arena is
     * never generated (no world copy, no id increment), and <code>null</code> is returned.
     *
     * @param int $modeId
     * @param \pocketmine\player\Player $creator
     * @return Arena|null
     */
    final public function createPrivateArena(int $modeId, \pocketmine\player\Player $creator): ?Arena
    {
        $event = new PlayerCreatePrivateGameEvent($creator, $modeId);
        $event->call();

        if ($event->isCancelled()) {
            return null;
        }

        $arena = $this->generateNewArena($modeId, true);
        $arena->setPrivate(true, $creator);
        $this->arenas[$arena->getId()] = $arena;

        return $arena;
    }

    public function getArenaByWorld(World $world): ?Arena
    {
        $explode_match = explode('Match-' . $this->getMinigameTag() . '-', $world->getFolderName());

        if (isset($explode_match[1])) {
            return $this->arenas[(int)$explode_match[1]] ?? null;
        }

        if ($this->hasWaitingLobby()) {
            $explode_waiting = explode('Waiting-' . $this->getMinigameTag() . '-', $world->getFolderName());

            if (isset($explode_waiting[1])) {
                return $this->arenas[(int)$explode_waiting[1]] ?? null;
            }
        }

        return null;
    }

    public function onDisable(): void
    {
        $this->getLogger()->info('§4' . $this->getDescription()->getName() . ' successfully disabled!');
    }
}