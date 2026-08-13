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

namespace NetherGames\NGEssentials\player;

use Closure;
use ErrorException;
use JsonException;
use libminigames\Arena;
use libReplay\session\replay\ReplayManager;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\events\NGPlayerTransferEvent;
use NetherGames\NGEssentials\kafka\KafkaServerTopic;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\ChatText;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\enforcement\Enforcement;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\pets\PetsManager;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\player\store\Store;
use NetherGames\NGEssentials\player\utils\StatsBarType;
use NetherGames\NGEssentials\player\utils\VoteManager;
use NetherGames\NGEssentials\player\worldfeatures\WorldFeatures;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Cluster;
use NetherGames\NGEssentials\servers\Server;
use NetherGames\NGEssentials\tasks\BroadcastAnnouncementsTask;
use NetherGames\NGEssentials\tasks\KnockbackTask;
use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\skins\SkinValidatorAdapter;
use NetherGames\NGEssentials\utils\SkinUtils;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;
use function json_decode;
use function json_encode;
use function mt_rand;
use function natcasesort;
use function str_replace;
use function str_split;
use function time;
use const JSON_THROW_ON_ERROR;

class PlayerManager extends BaseClass
{
    /** @var WorldFeatures */
    private WorldFeatures $worldFeatures;
    /** @var PetsManager|null */
    private ?PetsManager $petsManager = null;
    /** @var SocialManager */
    private SocialManager $socialManager;
    /** @var CosmeticHandler */
    private CosmeticHandler $cosmeticsHandler;
    /** @var Enforcement */
    private Enforcement $enforcementHandler;
    /** @var ChatManager */
    private ChatManager $chatManager;
    /** @var RankManager */
    private RankManager $rankManager;
    /** @var VoteManager */
    private VoteManager $voteManager;
    /** @var Store */
    private Store $store;
    /** @var KafkaServerTopic */
    private KafkaServerTopic $playerTransferTopic;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct($plugin);

        $this->enforcementHandler = new Enforcement($this);
        $this->cosmeticsHandler = new CosmeticHandler($this);
        $this->worldFeatures = new WorldFeatures($this);
        $this->socialManager = new SocialManager($this);
        $this->chatManager = new ChatManager($this);
        $this->rankManager = new RankManager($this);
        $this->voteManager = new VoteManager($this);

        $serverManager = $plugin->getServerManager();
        if (($enableLobbyHandling = $serverManager->enableLobbyHandling()) || in_array($plugin->getServerManager()->getServerType(), [ServerManager::SW, ServerManager::SETUP], true)) {
            $this->store = new Store($this);

            if ($enableLobbyHandling) {
                $this->petsManager = new PetsManager($this);

                $scheduler = $plugin->getScheduler();
                if ($serverManager->enableLobbyHandling()) {
                    $scheduler->scheduleRepeatingTask(new BroadcastAnnouncementsTask($plugin), 1200);
                    $scheduler->scheduleRepeatingTask(new KnockbackTask($plugin), 1);
                }
            }
        }

        $this->playerTransferTopic = new KafkaServerTopic("player_transfer", $plugin->getServerManager(), function (string $key, string $message) use ($plugin): void {
            $plugin->getPlayerData()->setData($key, json_decode($message, true, 512, JSON_THROW_ON_ERROR));

            $plugin->getLogger()->info("Received WaterdogPE data layer: $key");
        });
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function getCosmeticHandler(): CosmeticHandler
    {
        return $this->cosmeticsHandler;
    }

    public function getWorldFeatures(): WorldFeatures
    {
        return $this->worldFeatures;
    }

    public function getPetsManager(): ?PetsManager
    {
        return $this->petsManager;
    }

    public function getPlayerFromIdentifier(string $playerIdentifier): ?Player
    {
        if (($player = $this->getPlugin()->getServer()->getPlayerExact($playerIdentifier)) === null) {
            $player = $this->getPlugin()->getServer()->getPlayerByXuid($playerIdentifier);
        }

        return $player;
    }

    public function onPlayerNameChange(string $newPlayerName, string $oldPlayerName): void
    {
        $this->getPlugin()->getPlayerData()->setValue($newPlayerName, PlayerData::OLD_PLAYER_NAME, $oldPlayerName);
        $this->getSocialManager()->getFriendsManager()->updatePlayerName($newPlayerName, $oldPlayerName);
    }

    public function getSocialManager(): SocialManager
    {
        return $this->socialManager;
    }

    public function setupPlayer(Player $player, bool $finishedMFA = false): void
    {
        /** @var NGPlayer $player */
        $serverManager = $this->getPlugin()->getServerManager();
        $serverType = $serverManager->getServerType();
        $playerData = $this->getPlugin()->getPlayerData();
        $preloaded = $playerData->getBool($player, PlayerData::PRELOADED);

        $skin = $player->getSkin();
        $player->setOriginalSkin($skin);

        $rankManager = $this->getRankManager();
        $rankManager->updatePermissions($player);
        $rankManager->updateNameTag($player);

        if (MySQLCredentials::isDatabaseOnline()) {
            if ($preloaded) {
                $serverManager = $this->getPlugin()->getServerManager();
                MySQLCredentials::executeChange('player.send_server', ['xuid' => $player->getXuid(), 'last_server' => $serverManager->getUniqueId()]);
            } else {


                if (($locale = $playerData->getString($player, PlayerData::LOCALE)) === Translator::FALLBACK_LANGUAGE && ($clientLocale = Translator::translateLocale($player->getLocale())) !== Translator::FALLBACK_LANGUAGE) {
                    //save the client's language if it's different from NG fallback language
                    $playerData->setValue($player, PlayerData::LOCALE, $clientLocale);
                    $player->setNGLanguage($clientLocale);
                } else {
                    $player->setNGLanguage($locale);
                }

                if ($skin->getSkinId() === SkinValidatorAdapter::INVALID_SKIN) {
                    $player->sendMessage(TextFormat::RED . 'Your skin was changed because it was invalid.');
                }

                $serverManager = $this->getPlugin()->getServerManager();
                if ($playerData->getBool($player, PlayerData::FIRST_JOIN)) {
                    MySQLCredentials::executeInsert('player.send_first_join', ['xuid' => $player->getXuid(), 'player' => $player->getName(), 'first_joined' => time(), 'last_joined' => time(), 'last_server' => $serverManager->getUniqueId()], function () use ($player, $skin) {
                        SkinUtils::saveSkin($player, $skin);
                    });
                } else {
                    MySQLCredentials::executeChange('player.send_join', ['xuid' => $player->getXuid(), 'last_joined' => time(), 'last_server' => $serverManager->getUniqueId()]);
                    $this->getChatManager()->loadOfflineMessages($player);

                    SkinUtils::saveSkin($player, $skin);
                }

                $this->getVoteManager()->checkVote($player);

                if (Permissions::isStaff($player)) {
                    $this->getChatManager()->getGlobalChatManager()->sendStaffMessage(new ChatText(new RawMessage($player->getNameTag() . ' §r§6has joined the server!')));
                }
            }
        } else {
            if (($locale = $playerData->getString($player, PlayerData::LOCALE)) === Translator::FALLBACK_LANGUAGE && ($clientLocale = Translator::translateLocale($player->getLocale())) !== Translator::FALLBACK_LANGUAGE) {
                $playerData->setValue($player, PlayerData::LOCALE, $clientLocale);
                $player->setNGLanguage($clientLocale);
            } else {
                $player->setNGLanguage($locale);
            }

            if ($skin->getSkinId() === SkinValidatorAdapter::INVALID_SKIN) {
                $player->sendMessage(TextFormat::RED . 'Your skin was changed because it was invalid.');
            }
        }

        if ($serverType === ServerManager::REPLAY && ($replayId = $playerData->getInt($player, PlayerData::REPLAY)) !== 0) {
            $playerData->unsetValue($player, PlayerData::REPLAY);

            if (MySQLCredentials::isDatabaseOnline() && ($replayManager = ReplayManager::getInstance()) !== null) {
                $replayManager->loadReplay($player, $replayId);
            } else {
                $this->transferPlayer($player);
            }
        } elseif ($serverType === ServerManager::CREATIVE) {
            if (MySQLCredentials::isDatabaseOnline()) {
                if ($player->hasPermission(Permissions::RANK_ULTRA) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK)) {
                    $player->setAllowFlight(true);
                }

                if ($playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && $player->hasPermission(Permissions::RANK_TITAN) && !$playerData->getBool($player, PlayerData::NICK) && !$playerData->getBool($player, PlayerData::TRACK)) {
                    $player->getServer()->broadcastMessage($player->getNameTag() . ' §r§6has joined the server!', $player->getWorld()->getPlayers());
                }
            }

            $player->setEnergized();

            if (($spectatedName = $playerData->getString($player, PlayerData::TRACK)) === '') {
                LobbyItems::setLobbyInventory($player);
            } else {
                $this->getEnforcementHandler()->setTracking($player, $spectatedName, false);
            }
        }

        $playerData->setValue($player, PlayerData::SETUP, true);

        if ($player->spawned) {
            $player->setNoClientPredictions(false);

            $ev = new NGLoginEvent($player, $preloaded);
            $ev->call();

            $ev = new NGJoinEvent($player, $preloaded);
        } else {
            $ev = new NGLoginEvent($player, $preloaded);
        }
        $ev->call();
    }

    public function getVoteManager(): VoteManager
    {
        return $this->voteManager;
    }

    /**
     * @param Player $player
     * @param string|Cluster|Server $serverType
     * @param string $gameType
     * @param bool $ignoreFull
     * @param Closure|null $onMatchmakingFailure
     * @return bool
     */
    public function transferPlayer(Player $player, Server|string|Cluster $serverType = ServerManager::LOBBY, string $gameType = '', bool $ignoreFull = false, ?Closure $onMatchmakingFailure = null): bool
    {
        if ($player->isConnected()) {
            /** @var NGPlayer $player */
            $plugin = $this->getPlugin();
            $serverManager = $plugin->getServerManager();
            if ($serverType instanceof Server) {
                $cluster = $serverType->getCluster();
            } elseif ($serverType instanceof Cluster) {
                $cluster = $serverType;
            } else {
                $cluster = $serverManager->getCluster($serverType, $gameType);
            }

            if ($serverManager->getQueuedCluster($player) === $cluster) {
                $cluster->removeFromQueue($player);

                $player->sendMessage(TextFormat::GREEN . 'Successfully left the queue!');
                return false;
            }

            $socialManager = $this->getSocialManager();
            $partyManager = $socialManager->getPartyManager();
            $playerData = $plugin->getPlayerData();

            if (($party = $partyManager->getParty($player)) === null) {
                $transfer = function (Player $player, Server $server): bool {
                    $this->forceTransfer($player, $server);
                    return true;
                };

                $playerData->setValue($player, PlayerData::PRE_TRANSFER, true);
                $cancelTransfer = static fn() => $playerData->unsetValue($player, PlayerData::PRE_TRANSFER);
            } else {
                $transfer = static function (Player $player, Server $server) use ($socialManager): bool {
                    return $socialManager->getPartyManager()->transferParty($player, $server);
                };

                $players = $partyManager->getPlayers($party);

                foreach ($players as $p) {
                    $playerData->setValue($p, PlayerData::PRE_TRANSFER, true);
                }

                $cancelTransfer = static function () use ($playerData, $players): void {
                    foreach ($players as $p) {
                        $playerData->unsetValue($p, PlayerData::PRE_TRANSFER);
                    }
                };
            }

            if ($serverType instanceof Server) {
                return $transfer($player, $serverType);
            }

            $serverManager->findBestServer($player, $cluster, $ignoreFull, static function (Server $server) use ($transfer, $player) {
                $transfer($player, $server);
            }, static function () use ($onMatchmakingFailure, $cancelTransfer): void {
                $cancelTransfer();

                if ($onMatchmakingFailure !== null) {
                    $onMatchmakingFailure();
                }
            });
            return true;
        }

        return false;
    }

    /**
     * @param Player $player
     * @param Server|null $server
     * @return void
     */
    public function forceTransfer(Player $player, ?Server $server = null): void
    {
        if (!$player->isConnected()) {
            return;
        }

        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();
        $playerData = $plugin->getPlayerData();

        /** @var NGPlayer $player */
        if ($server === null) {
            $cluster = $serverManager->getCluster(ServerManager::LOBBY);

            $playerData->setValue($player, PlayerData::PRE_TRANSFER, true);

            $serverManager->findBestServer($player, $cluster, true, function (Server $server) use ($player) {
                $this->forceTransfer($player, $server);
            }, static fn() => $playerData->unsetValue($player, PlayerData::PRE_TRANSFER));
        } else {
            $event = new NGPlayerTransferEvent($player, $server);
            $event->call();

            if ($event->isCancelled()) {
                return;
            }

            $playerData->unsetValue($player, PlayerData::PRE_TRANSFER);
            $playerData->setValue($player, PlayerData::TRANSFER, true);

            try {
                $data = json_encode($this->getPlugin()->getPlayerData()->getData($player), JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $data = '';
            }

            $this->getPlugin()->getLogger()->info("Using WaterdogPE Transfer: {$player->getName()}");

            $this->playerTransferTopic->send(
                $server,
                $player->getName(),
                $data,
                function () use ($player, $server): void {
                    if ($player->isConnected()) {
                        $player->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getUniqueId(), -1, false), true);

                        foreach ($this->getEnforcementHandler()->isTracking($player->getName()) as $staffMember) {
                            $this->forceTransfer($staffMember, $server);
                        }
                    }
                }
            );

            $player->setInvisible();
            $player->setNoClientPredictions();
        }
    }

    public function getEnforcementHandler(): Enforcement
    {
        return $this->enforcementHandler;
    }

    public function getNameTag(Player $player, string $colour = TextFormat::YELLOW, bool $hideLevels = false, bool $hideGuild = false): string
    {
        $playerData = $this->getPlugin()->getPlayerData();
        $hideLevels = $hideLevels || ($serverManager = $this->getPlugin()->getServerManager())->getServerType() === ServerManager::CREATIVE || $serverManager->isMMOGame();

        if ($playerData->getBool($player, PlayerData::NICK)) {
            $nick = $playerData->getString($player, PlayerData::NICK);

            if ($hideLevels) {
                return $colour . $nick;
            }

            return $this->getLevelFormat(mt_rand(0, 100)) . ' §r' . $colour . $nick;
        }

        $tag = '';
        if (!$hideGuild && !$playerData->getBool($player, PlayerData::HIDE_GUILD_TAG) && ($guild = $this->getSocialManager()->getGuildsManager()->getGuild($playerData->getInt($player, PlayerData::GUILD))) !== null && $guild->getTag() !== '') {
            $tag = ' ' . TextFormat::BOLD . $guild->getTag();
        }

        $levels = '';
        if (!$hideLevels) {
            $level = (int)ExperienceUtils::getLevelFromXp($playerData->getInt($player, PlayerData::XP));
            $levels = $this->getLevelFormat($level) . ' §r';
        }

        $muted = $playerData->getBool($player, PlayerData::MUTED) ? CustomIcon::MUTE . ' ' : '';
        $default = $levels . $playerData->getString($player, PlayerData::RANKTAG) . $tag;
        if ($colour === TextFormat::YELLOW) {
            return $muted . $default;
        }

        return $muted . str_replace($player->getName(), $colour . $player->getName() . $tag, $default);
    }

    public function getLevelFormat(int $level): string
    {
        $format = '';

        if ($level < 25) {
            $format .= TextFormat::WHITE . $level;
        } else if ($level < 50) {
            $format .= TextFormat::GRAY . $level;
        } else if ($level < 75) {
            $format .= TextFormat::YELLOW . $level;
        } elseif ($level < 100) {
            $format .= TextFormat::GREEN . $level;
        } else if ($level < 125) {
            $format .= TextFormat::AQUA . $level;
        } else if ($level < 150) {
            $format .= TextFormat::BLUE . $level;
        } elseif ($level < 175) {
            $format .= TextFormat::RED . $level;
        } elseif ($level < 200) {
            $format .= TextFormat::LIGHT_PURPLE . $level;
        } else {
            $num = str_split((string)$level);
            $format .= TextFormat::BOLD;

            if ($level < 300) {
                $format .= TextFormat::AQUA . $num[0] . TextFormat::WHITE . $num[1] . TextFormat::AQUA . $num[2];
            } elseif ($level < 400) {
                $format .= TextFormat::GOLD . $num[0] . TextFormat::YELLOW . $num[1] . TextFormat::GOLD . $num[2];
            } elseif ($level < 500) {
                $format .= TextFormat::DARK_AQUA . $num[0] . TextFormat::AQUA . $num[1] . TextFormat::DARK_AQUA . $num[2];
            } elseif ($level < 600) {
                $format .= TextFormat::DARK_GREEN . $num[0] . TextFormat::GREEN . $num[1] . TextFormat::DARK_GREEN . $num[2];
            } elseif ($level < 700) {
                $format .= TextFormat::DARK_BLUE . $num[0] . TextFormat::BLUE . $num[1] . TextFormat::DARK_BLUE . $num[2];
            } elseif ($level < 800) {
                $format .= TextFormat::DARK_RED . $num[0] . TextFormat::RED . $num[1] . TextFormat::DARK_RED . $num[2];
            } elseif ($level < 900) {
                $format .= TextFormat::DARK_PURPLE . $num[0] . TextFormat::LIGHT_PURPLE . $num[1] . TextFormat::DARK_PURPLE . $num[2];
            } elseif ($level < 1000) {
                $format .= TextFormat::DARK_GRAY . $num[0] . TextFormat::GRAY . $num[1] . TextFormat::DARK_GRAY . $num[2];
            } else {
                $format .= TextFormat::RED . $num[0] . TextFormat::GOLD . $num[1] . TextFormat::GREEN . $num[2] . TextFormat::LIGHT_PURPLE . $num[3];
            }
        }

        return $format;
    }

    /**
     * @internal getDisplayName() should be used instead!
     */
    public function getPlayerColouredName(Player $player, string $default = TextFormat::GRAY, bool $ignoreNick = false): string
    {
        if (!$ignoreNick) {
            $playerData = $this->getPlugin()->getPlayerData();

            if ($playerData->getBool($player, PlayerData::NICK)) {
                return $default . $playerData->getString($player, PlayerData::NICK);
            }
        }

        return (match (true) {
                $player->hasPermission(Permissions::RANK_OWNER) => TextFormat::RED,
                $player->hasPermission(Permissions::RANK_DIRECTOR) => TextFormat::GREEN,
                $player->hasPermission(Permissions::RANK_ADVISOR) => TextFormat::RED,
                $player->hasPermission(Permissions::RANK_DEVELOPER) => TextFormat::LIGHT_PURPLE,
                $player->hasPermission(Permissions::RANK_ADMIN) => TextFormat::GOLD,

                $player->hasPermission(Permissions::RANK_TITAN) => TextFormat::RED,
                $player->hasPermission(Permissions::RANK_LEGEND) => TextFormat::AQUA,
                $player->hasPermission(Permissions::RANK_EMERALD) => TextFormat::GREEN,
                $player->hasPermission(Permissions::RANK_ULTRA) => TextFormat::GOLD,

                $player->hasPermission(Permissions::TIER_PLATINUM) => TextFormat::DARK_PURPLE,
                $player->hasPermission(Permissions::TIER_DIAMOND) => TextFormat::DARK_RED,
                $player->hasPermission(Permissions::TIER_SAPPHIRE) => TextFormat::DARK_BLUE,
                $player->hasPermission(Permissions::TIER_AMETHYST) => TextFormat::LIGHT_PURPLE,
                $player->hasPermission(Permissions::TIER_OPAL) => TextFormat::BLUE,
                $player->hasPermission(Permissions::TIER_GOLD) => TextFormat::GOLD,
                $player->hasPermission(Permissions::TIER_SILVER) => TextFormat::GRAY,
                $player->hasPermission(Permissions::TIER_BRONZE) => TextFormat::YELLOW,
                default => $default
            }) . $player->getName();
    }

    public function updatePlayerVisibility(Player $player, ?World $world = null): void
    {
        if ($world === null) {
            $world = $player->getWorld();
        }

        $plugin = $this->getPlugin();
        $players = array_diff($plugin->getServer()->getOnlinePlayers(), [$player]);

        if ($plugin->getServerManager()->enableLobbyFeatures($world)) {
            $playerData = $plugin->getPlayerData();
            $isTracking = $playerData->getBool($player, PlayerData::TRACK);
            $isHidingPlayers = $playerData->getBool($player, PlayerData::HIDE_PLAYERS);
            $show = $isTracking || !$isHidingPlayers;

            foreach ($players as $p) {
                if ($show) {
                    $player->showPlayer($p);
                } else {
                    $player->hidePlayer($p);
                }

                if (!$isHidingPlayers) {
                    if ($playerData->getBool($p, PlayerData::TRACK) || !$playerData->getBool($p, PlayerData::HIDE_PLAYERS)) {
                        $p->showPlayer($player);
                    } else {
                        $p->hidePlayer($player);
                    }
                }
            }
        } else {
            foreach ($players as $p) {
                $p->showPlayer($player);
                $player->showPlayer($p);
            }
        }
    }

    public function sendLobbyScoreBoard(Player $player): void
    {
        $serverManager = $this->getPlugin()->getServerManager();
        $playerData = $this->getPlugin()->getPlayerData();

        try {
            $serverId = $serverManager->getId();
        } catch (ErrorException) {
            $serverId = 1;
        }

        $scoreboard = $this->getPlugin()->getServerData()->getScoreBoard();
        $scoreboard->addPlayer($player);
        $scoreboard->setLines([$player],
            [
                1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                2 => '',
                3 => CustomIcon::PLAYERS_TINY . "Players: " . TextFormat::GREEN . $serverManager->getGlobalPlayerCount(),
                4 => CustomIcon::WORLD . "Lobby: " . TextFormat::GREEN . '#' . $serverId,
                5 => '',
                6 => CustomIcon::KEY . "Keys: " . TextFormat::GREEN . $playerData->getInt($player, PlayerData::KEYS),
                7 => CustomIcon::MYSTIC_CHEST . "Credits: " . TextFormat::GREEN . $playerData->getInt($player, PlayerData::STATUS_CREDITS),
                8 => CustomIcon::EXPERIENCE . "Level: " . $this->getLevelFormat((int)ExperienceUtils::getLevelFromXp($playerData->getInt($player, PlayerData::XP))),
                9 => ''
            ]);
    }

    /**
     * @param Player[] $players
     *
     * @return string[]
     */
    public function getPlayerNames(array $players, bool $ignoreNick = false): array
    {
        $plugin = $this->getPlugin();
        $players = array_filter(
            $players,
            static fn(Player $player) => !$player->isClosed() && !$plugin->getPlayerData()->getBool($player, PlayerData::TRACK)
        );

        if ($ignoreNick) {
            $names = array_map(static function (Player $player): string {
                return $player->getName();
            }, $players);
        } else {
            $names = array_map(function (Player $player): string {
                return $this->getPlayerName($player);
            }, $players);
        }

        natcasesort($names);

        return array_values($names);
    }

    public function getPlayerName(Player $player): string
    {
        if (($nick = $this->getPlugin()->getPlayerData()->getString($player, PlayerData::NICK)) !== '') {
            return $nick;
        }

        return $player->getName();
    }

    private function isFPSPlayer(Player $player): bool
    {
        return $player instanceof NGPlayer && (!$player->isLoaded() || $this->getPlugin()->getPlayerData()->getBool($player, PlayerData::FPS_MODE));
    }

    /**
     * @param Player[] $players
     * @return array{0: Player[], 1: Player[]}
     */
    public function splitFPSPlayers(array $players): array
    {
        $fpsPlayers = [];
        $nonFpsPlayers = [];

        foreach ($players as $player) {
            if ($this->isFPSPlayer($player)) {
                $fpsPlayers[] = $player;
            } else {
                $nonFpsPlayers[] = $player;
            }
        }

        return [$fpsPlayers, $nonFpsPlayers];
    }

    /**
     * @param Player[] $players
     * @return Player[]
     */
    public function unsetFPSPlayers(array $players): array
    {
        return array_filter($players, fn(Player $player) => !$this->isFPSPlayer($player));
    }

    /**
     * @param Player[] $players
     * @return Player[]
     */
    public function getFPSModePlayers(array $players): array
    {
        return array_filter($players, fn(Player $player) => $this->isFPSPlayer($player));
    }

    /**
     * if the player is offline, the name is strtolowered!!!
     *
     * @param string $playerName
     * @return IPlayer|Player
     */
    public function getBestMatchingPlayer(string $playerName): IPlayer
    {
        $server = $this->getPlugin()->getServer();

        if ((($player = $server->getPlayerByPrefix($playerName)) === null) && ($player = $this->getPlugin()->getPlayerData()->getPlayerByNick($playerName)) === null) {
            $player = $server->getOfflinePlayer($playerName);
        }

        return $player;
    }

    /**
     * @param Player $player
     * @param string $nick
     * @param Closure $onValid
     */
    public function checkName(Player $player, string $nick, Closure $onValid): void
    {
        $filter = $this->getChatManager()->getFilter();

        if ($filter->checkAdvertising($player, $nick) && $filter->checkImpersonation($player, $nick) && Player::isValidUserName($nick)) {
            $filter->checkSwearing($player, $nick, $onValid);
        } else {
            $player->sendMessage(TextFormat::RED . "That's not a valid name.");
        }
    }

    public function getChatManager(): ChatManager
    {
        return $this->chatManager;
    }

    public function setStatsBar(Player $player): void
    {
        $playerData = $this->getPlugin()->getPlayerData();
        $gameSettings = $playerData->getGameSettings();

        $level = 0;
        $progress = 0.0;

        switch (StatsBarType::from($gameSettings->getInt($player, GameSettings::STATS_BAR))) {
            case StatsBarType::XP:
                $xp = $playerData->getInt($player, PlayerData::XP);
                $newLevel = ExperienceUtils::getLevelFromXp($xp);

                $level = (int)$newLevel;
                $progress = $newLevel - $level;
                break;
            case StatsBarType::CREDITS:
                $level = $playerData->getInt($player, PlayerData::STATUS_CREDITS);

                $rankManager = $this->getRankManager();
                $currentTier = $rankManager->getTierFromCredits($level);

                if (($nextTier = $rankManager->getNextTier($currentTier)) === null) {
                    $progress = 1.0;
                } else {
                    $tierCredits = $currentTier?->getCredits() ?? 0;
                    $tierDiff = $nextTier->getCredits() - $tierCredits;
                    if ($tierDiff <= 0) {
                        $progress = 1.0;
                    } else {
                        $progress = ($level - $tierCredits) / $tierDiff;
                    }
                }
                break;
            case StatsBarType::OFF:
                break;
        }

        $player->getXpManager()->setXpAndProgress($level > 24791 ? 0 : $level, $progress);
    }

    /**
     * @param Player $player
     * @param bool $returnGame
     * @return bool|Arena
     */
    public function isInArena(Player $player, bool $returnGame = false): Arena|bool
    {
        if ((($game = $this->getPlugin()->getServerManager()->getGamePlugin()) !== null) && ($arena = $game->getArena($player)) !== null) {
            if ($returnGame) {
                return $arena;
            }

            return true;
        }

        return false;
    }

    public function getRankManager(): RankManager
    {
        return $this->rankManager;
    }
}
