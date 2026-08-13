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
use libminigames\events\MinigameJoinEvent;
use libminigames\utils\ArenaConfig;
use libminigames\utils\generators\VoidGenerator;
use libReplay\session\record\RecordManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\thread\NGThreadPool;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
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
use function array_rand;
use function array_search;
use function count;
use function explode;
use function glob;
use function in_array;
use function str_replace;
use const GLOB_ONLYDIR;

/**
 * A minigame service for the NetherGamesMC production server. This
 * is made abstract so that developers can extend this class, as well
 * as manage and monitor gamemodes easily.
 *
 * <p>A <code>Minigame<code> provides a: replay system, join and leave system,
 * queuing system and arena management system. This class is intended to
 * provide basic information about the game that is currently running.
 *
 * <p>In addition to execution and life-cycle control methods, this method
 * creates an arena and destroys them after they are no longer in use
 * which are {@link Minigame::getArenas()} that are intended to aid in managing,
 * creating and monitoring arenas.
 *
 * <p>On the other hand, methods like total players count and arena lifetime length
 * can be hardcoded by their respective classes, no configurations are required for these
 * config.
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
    /** @var NGEssentials */
    private NGEssentials $ess;

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
     * Specifies the available modes for this game, under production server,
     * only one mode will be chosen (As seen in lobby if you have played it before)
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
     * <p>As seen in this code, only 2 mode appears to be registered, though you can attempt
     * to register more modes and provide your own constant values. This mode will be used to multiply
     * the amount of players needed in an arena, <code>$mode * 4</code>, where 4 is is the maximum players
     * required in arena per modes.
     *
     * <p>String value returned are used in scoreboard.
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
     * where you can register your arena listeners and config where appropriate,
     * just like how you want it to be in both onload or onEnable functions.
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

        $ess = $this->getServer()->getPluginManager()->getPlugin('NGEssentials');
        if ($ess instanceof NGEssentials) {
            $this->ess = $ess;

            $this->registerClasses();

            $this->getServer()->getCommandMap()->register('requeue', new RequeueCommand($this));

            $this->getLogger()->info('§2' . $this->getDescription()->getName() . ' successfully enabled!');
        } else {
            $this->getServer()->shutdown();
        }
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
     * Initializes methods that are needed such as listeners which is {@see MinigameListener},
     * config files or anything appropriate, just like how you handle {@see PluginBase::onEnable()}.
     *
     * <p>This function also being used to register leaderboards, {@see LeaderboardData::load()},
     * if this arena has a death and kills scores.
     */
    abstract public function registerClasses(): void;

    public function getMapDisplayName(string $mapName, bool $includeTag = false): string
    {
        $e = explode('-', $mapName);
        $mapDisplayName = str_replace('_', ' ', $e[array_key_last($e)]);

        if ($includeTag) {
            $mapTag = $this->getArenaConfig()->getTag($mapName);

            return $mapDisplayName . match ($mapTag) {
                    ArenaConfig::TAG_WINTER => ' ' . CustomIcon::CHRISTMAS_HAT,
                    ArenaConfig::TAG_HALLOWEEN => ' ' . CustomIcon::PUMPKIN,
                    ArenaConfig::TAG_NEW => ' ' . CustomIcon::NEW,
                    ArenaConfig::TAG_SUMMER => ' ' . CustomIcon::SUN,
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
     * This method is considered as internal use, attempt to not use this function regardless of any
     * consequences.
     *
     * @param Player $player The player requested to join this arena.
     * @param int $modeId The mode of the arena requested, as seen in {@link Minigame::getModes()}
     * @return bool The value indicates that the player has successfully joined to their requested arena.
     */
    final public function joinArena(Player $player, int $modeId = -1): bool
    {
        /** @var NGPlayer $player */
        $ess = $this->getEssentials();
        $playerManager = $ess->getPlayerManager();
        $partyManager = $playerManager->getSocialManager()->getPartyManager();

        if ($this->getArena($player) !== null) {
            $player->sendMessage('§cYou\'re already in a ' . $this->getMinigameName() . ' match!');
            return false;
        }

        if ($ess->getPlayerData()->getBool($player, PlayerData::TRACK)) {
            $player->sendMessage(TextFormat::RED . "You're currently in tracking mode. Leave to join a game.");
            return false;
        }

        $size = $this->getQueueSize($player);

        if ($modeId === -1) {
            if (($gameType = $ess->getServerManager()->getGameType()) === '') {
                /** @var int|null $key */
                $key = array_key_first($this->getModes());

                if ($key === null) {
                    $player->sendMessage(TextFormat::RED . "Something went wrong.");
                    return false;
                }

                $modeId = $key;
            } else {
                $modeId = $this->getModeId($gameType);
            }
        }

        if (($party = $partyManager->getParty($player)) !== null && $party->hasPrivateGames()) {
            $arena = $this->generateNewArena($modeId, true);

            $this->arenas[$arena->getId()] = $arena;
        } else {
            $arena = $this->getFreeArena($modeId, $size, $ess->getServerManager()->getQueuingMode($player));

            if ( // If either one of the following conditions are met, the player will be transferred back to the lobby
                $arena === null ||
                ($party !== null && $arena instanceof TeamArena &&
                    ( // This will happen because a party has too many players than allowed to join a game
                        ($arena->getTeamSize() < $party->getTotalMembers() && $arena->getMaxSize() !== $party->getTotalMembers()) || // Party is too big to join the team and party size is not equal to the max arena size
                        (!$player->hasPermission(Permissions::RANK_LEGEND) && $party->getTotalMembers() > $arena->getMaxSize()) // Player doesn't have legend rank (private games access) and there are more party members than how many can join the arena
                    )
                )
            ) {
                $player->sendToastNotification(TextFormat::RED . "Could not join!", TextFormat::RED . "Your party is too big to join this game.");
                $player->sendMessage(TextFormat::YELLOW . match ($player->hasPermission(Permissions::RANK_LEGEND)) {
                        true => "Create a private game if you want to play with your full party.",
                        default => "You can buy a §l§bLEGEND §r" . TextFormat::YELLOW . "rank to create a private game and play with your full party."
                    });

                $playerManager->transferPlayer($player);
                return false;
            }
        }

        $event = new MinigameJoinEvent($player, $arena, $modeId, $arena->getId());
        $event->call();

        if (!$event->isCancelled()) {
            return $arena->addPlayer($player);
        }

        return false;
    }

    final public function getEssentials(): NGEssentials
    {
        return $this->ess;
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
     * @return string
     */
    final public function getMinigameName(): string
    {
        return ServerManager::getName($this->getMinigameTag());
    }

    /**
     * The name of the game, full name if it is possible. This name will be used
     * for your command label and arena world filename.
     *
     * @return string
     */
    public function getMinigameTag(): string
    {
        return $this->getEssentials()->getServerManager()->getServerType();
    }

    /**
     * @param Player $player
     * @return int The amount of players in their party.
     *
     * @internal
     */
    public function getQueueSize(Player $player): int
    {
        $partyManager = $this->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();

        if (($party = $partyManager->getParty($player)) !== null) {
            $size = count($party->getAll());
        } else {
            $size = 1;
        }

        return $size;
    }

    /**
     * Requeues a player into the same game mode they just played.
     *
     * @param Player $player The player to requeue
     * @param Arena $arena The arena the player is currently in
     * @param string $mode The game mode to requeue into
     */
    final public function requeuePlayer(Player $player, Arena $arena, string $mode): void
    {
        /** @var NGPlayer $player */
        $arena->removePlayer($player, events\MinigameQuitEvent::END, true, false);

        if ($this->isStandAloneGame()) {
            $ess = $this->getEssentials();
            $playerManager = $ess->getPlayerManager();
            $serverManager = $ess->getServerManager();

            if (($gameType = $serverManager->getGameType()) === '' || $gameType === $mode) {
                $onMatchmakingFailure = function () use ($player): void {
                    if ($player->isConnected()) {
                        $this->joinArena($player);
                    }
                };

                if ($this->canJoinArena($player, $this->getModeId($mode)) || !$playerManager->transferPlayer($player, $serverManager->getServerType(), $serverManager->getGameType(), true, $onMatchmakingFailure)) {
                    $onMatchmakingFailure();
                }
            } else {
                $onMatchmakingFailure = static function () use ($playerManager, $player): void {
                    if ($player->isConnected()) {
                        $playerManager->transferPlayer($player);
                    }
                };

                if (!$playerManager->transferPlayer($player, $serverManager->getServerType(), $mode, true, $onMatchmakingFailure)) {
                    $onMatchmakingFailure();
                }
            }
        } else {
            $this->joinArena($player, $this->getModeId($mode));
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
     * Get an available arena based on a queueing type given in variable <code>$queuingType</code>,
     * this call will attempt to iterate all possible arenas that is waiting. Only the first result
     * of an arena will be returned. However, if there is no possible arena is available, a new
     * arena will be created.
     *
     * <p>Queuing-specific, a technique introduced by NetherGamesMC developers to allow only to a specific
     * targeted players such as mobile users, to improve PVP against desktop users.
     *
     * Constant values below are the only values for <code>$queuingType</code>.
     * <code>
     *  // Prefer normal queueing, which desktop users and mobile users will
     *  // be joined in this game.
     *  public const QUEUING_GLOBAL = 0;
     *  public const QUEUING_PREFER_MOBILE = 1;
     *
     *  // Force mobile users, desktop users will not be allowed to join this arena
     *  public const QUEUING_FORCE_MOBILE = 2;
     * </code>
     *
     * <p><em>However, this method implies that most of its use are used internally.</em>
     *
     * @param int $modeId The specific mode set in {@link Minigame::getModes()}
     * @param int $size The size of a player party.
     * @param string $queuingType The type of a queue.
     * @return Arena|null Returns null when the size exceeds the maximum size of the arena.
     */
    public function getFreeArena(int $modeId, int $size, string $queuingType): ?Arena
    {
        $bestArena = null;
        $playerCount = 0;

        foreach ($this->getQueuingArenas($modeId, $queuingType) as $arena) {
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

            if ($this->isStandAloneGame()) {
                $this->updateQueuing($modeId);
            }
            return $arena;
        }

        return $bestArena;
    }

    /**
     * Queries an arena that is ready to receive queued players.
     * Unlike {@link generateNewArena()}, this function reuses the arena that has recently being created.
     *
     * @param int $modeId The specific mode of a game, {@see Minigame::getModes()}
     * @param string $queuingMode The type of queue. {@see Minigame::getFreeArena()}
     * @return Arena[]
     */
    public function getQueuingArenas(int $modeId, string $queuingMode = self::QUEUING_GLOBAL): array
    {
        $queuingArenas = [];
        $queuingMobileArenas = [];

        foreach ($this->getArenas($modeId) as $arena) {
            if (!$arena->isFull() && $arena->isWaiting()) {
                if ($arena->isTouchOnly()) {
                    if ($queuingMode === self::QUEUING_PREFER_MOBILE) {
                        $queuingMobileArenas[] = $arena;
                    } elseif ($queuingMode === self::QUEUING_FORCE_MOBILE) {
                        $queuingArenas[] = $arena;
                    }
                } elseif ($queuingMode !== self::QUEUING_FORCE_MOBILE) {
                    $queuingArenas[] = $arena;
                }
            }
        }

        return array_merge($queuingMobileArenas, $queuingArenas);
    }

    /**
     * Allow hub queuing for the match, hub queuing allows players to teleports
     * to the server's lobby after the match has finished/completed.
     *
     * @return bool
     */
    public function isStandAloneGame(): bool
    {
        return $this->getEssentials()->getServerManager()->getGameType() !== '';
    }

    public function updateQueuing(int $modeId): void
    {
        if (!$this->isStandAloneGame()) {
            return;
        }

        $queuingArenas = [];
        $queuingMobileArenas = [];

        foreach ($this->getArenas($modeId) as $arena) {
            if ($arena->isWaiting() && count($arena->getPlayers(false)) < 0.7 * $arena->getMaxSize()) {
                if ($arena->isTouchOnly()) {
                    $queuingMobileArenas[] = $arena;
                } else {
                    $queuingArenas[] = $arena;
                }
            }
        }

        $this->getEssentials()->getServerManager()->setQueuing(count($queuingArenas) !== 0, count($queuingMobileArenas) !== 0);
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

    public function canJoinArena(NGPlayer $player, int $modeId): bool
    {
        $queuingType = $this->getEssentials()->getServerManager()->getQueuingMode($player);
        $size = $this->getQueueSize($player);

        foreach ($this->getQueuingArenas($modeId, $queuingType) as $arena) {
            if ($arena->getSize() >= $size) {
                return true;
            }
        }

        return false;
    }

    /**
     * You know this is right?
     *
     * @param int $cause An {@link EntityDamageEvent::getCause()} cause id.
     * @param bool $tagged Indicate that this event has a damager caused by other player.
     * @return string Your random generated kill message.
     */
    final public function getRandomKillMessage(int $cause, bool $tagged = false): string
    {
        switch ($cause) {
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                $messages = [
                    '{PLAYER} §r§7was killed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was struck down by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was melted by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was filled full of lead by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7died in combat with {DAMAGER}§r§7.',
                    '{PLAYER} §r§7fell to the great marksmanship of {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was out of the league of {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was no match for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was glazed in BBQ sauce by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was not spicy enough for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was wrapped into a gift for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was put on the naughty list by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned into gingerbread by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was bit by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7be sent to Davy Jones\' locker by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7be killed by magic by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was spooked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was totally spooked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was tragically backstabbed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was heartlessly let go by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                    '{PLAYER} §r§7got roasted by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was bested by {DAMAGER}§r§7.'
                ];
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                $messages = [
                    '{PLAYER} §r§7was killed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was struck down by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was melted by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was filled full of lead by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7died in combat with {DAMAGER}§r§7.',
                    '{PLAYER} §r§7fell to the great marksmanship of {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was out of the league of {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was no match for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was glazed in BBQ sauce by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was not spicy enough for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was wrapped into a gift for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was put on the naughty list by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned into gingerbread by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was bit by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7be sent to Davy Jones\' locker by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7be killed by magic by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was spooked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was totally spooked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was tragically backstabbed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was heartlessly let go by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                    '{PLAYER} §r§7got roasted by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was bested by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was shot by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was thrown chilli powder at by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7caught the ball thrown by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was shot and killed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7be killed with metal by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was remotely spooked by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7heart was pierced by {DAMAGER}§r§7.'
                ];
                break;
            case EntityDamageEvent::CAUSE_FALL:
                if ($tagged) {
                    $messages = [
                        '{PLAYER} §r§7was knocked off a cliff by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7fought to the edge with {DAMAGER}§r§7.',
                        '{PLAYER} §r§7fell to the great marksmanship of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7stumbled off a ledge with the help of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was out of the league of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge spilled by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7hit the hard wood floor because of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was pushed down a slope by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned into gingerbread by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was spooked off the map by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was heartlessly let go by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                        '{PLAYER} §r§7got roasted by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was bested by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was knocked off the edge by {DAMAGER}§r§7.'
                    ];
                } else {
                    $messages = [
                        '{PLAYER} §r§7fell to their death.',
                        '{PLAYER} §r§7fell off a cliff.',
                        '{PLAYER} §r§7was turned to dust.',
                        '{PLAYER} §r§7was turned to ash.',
                        '{PLAYER} §r§7stumbled off a ledge.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge.',
                        '{PLAYER} §r§7hit the hard wood floor.'
                    ];
                }
                break;
            case EntityDamageEvent::CAUSE_VOID:
                if ($tagged) {
                    $messages = [
                        '{PLAYER} §r§7was knocked into the void by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was knocked off a cliff by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7fought to the edge with {DAMAGER}§r§7.',
                        '{PLAYER} §r§7fell to the great marksmanship of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7stumbled off a ledge with the help of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was out of the league of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge spilled by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was pushed down a slope by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned into gingerbread by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7howled into the void for {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was cannonballed to death by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was spooked off the map by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was heartlessly let go by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was delivered into nothingness by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                        '{PLAYER} §r§7got roasted by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was bested by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was knocked off the edge by {DAMAGER}§r§7.'
                    ];
                } else {
                    $messages = [
                        '{PLAYER} §r§7fell to their death.',
                        '{PLAYER} §r§7fell into the void.',
                        '{PLAYER} §r§7fell off a cliff.',
                        '{PLAYER} §r§7was turned to dust.',
                        '{PLAYER} §r§7was turned to ash.',
                        '{PLAYER} §r§7stumbled off a ledge.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge.',
                        '{PLAYER} §r§7howled into the void.',
                        '{PLAYER} §r§7fell into nothingness.'
                    ];
                }
                break;
            case EntityDamageEvent::CAUSE_LAVA:
                if ($tagged) {
                    $messages = [
                        '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was melted by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7fell to the great marksmanship of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7stumbled off a ledge with the help of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was out of the league of {DAMAGER}§r§7.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge spilled by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was turned into gingerbread by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was cannonballed to death by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was heartlessly let go by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                        '{PLAYER} §r§7got roasted by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                        '{PLAYER} §r§7was bested by {DAMAGER}§r§7.'
                    ];
                } else {
                    $messages = [
                        '{PLAYER} §r§7fell to their death.',
                        '{PLAYER} §r§7fell off a cliff.',
                        '{PLAYER} §r§7was turned to dust.',
                        '{PLAYER} §r§7was turned to ash.',
                        '{PLAYER} §r§7stumbled off a ledge.',
                        '{PLAYER} §r§7slipped in BBQ sauce off the edge.'
                    ];
                }
                break;
            default:
                $messages = [
                    '{PLAYER} §r§7died.'
                ];
                break;
        }

        return $messages[array_rand($messages)];
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

        $worldManager = $this->getServer()->getWorldManager();

        if (($world = $worldManager->getWorldByName($worldName = $arena->getMatchWorldName())) !== null) {
            if (($recordManager = RecordManager::getInstance()) !== null) {
                $recordManager->stopRecording($world, $arena->getStatus() === Arena::STATUS_FINISHING);
            }
            $this->getEssentials()->getEntityManager()->removeEntities($world);

            $world->setAutoSave(false);
            $worldManager->unloadWorld($world);
        }

        NGThreadPool::getInstance()->submitTask(new FileDeleteAsyncTask(Path::join($this->getServer()->getDataPath(), 'worlds', $worldName)));
    }

    final public function removeWaitingLobby(Arena $arena): void
    {
        $worldManager = $this->getServer()->getWorldManager();

        if (($world = $worldManager->getWorldByName($worldName = $arena->getWaitingLobbyWorldName())) !== null) {
            $this->getEssentials()->getEntityManager()->removeEntities($world);
            $worldManager->unloadWorld($world);
        }

        NGThreadPool::getInstance()->submitTask(new FileDeleteAsyncTask(Path::join($this->getServer()->getDataPath(), 'worlds', $worldName)));
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