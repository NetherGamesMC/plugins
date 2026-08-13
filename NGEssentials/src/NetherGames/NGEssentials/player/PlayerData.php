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

use Generator;
use JsonException;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\social\PlayerSocialInfo;
use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\DateUtils;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_keys;
use function array_reduce;
use function array_search;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function stripos;
use function strlen;
use function strtolower;
use function time;
use function ucfirst;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;

class PlayerData extends BaseClass
{
    private const COLUMN_NAMES = [
        self::LOCALE => 'locale',
        self::LAST_SERVER => 'last_server',
        self::LAST_JOINED => 'last_joined',
        self::FIRST_JOINED => 'first_joined',
        self::VOTE_TIME => 'vote_time',
        self::XP => 'xp',
        self::STATUS_CREDITS => 'status_credits',
        self::COSMETICS => 'cosmetics',
        self::STORE_DATA => 'store_data',
        self::KEYS => 'crate_keys',
        self::EXTRA_FRIENDS => 'extra_friends',
        self::HIDE_PLAYERS => 'hide_players',
        self::PET => 'pet',
        self::PET_NAME => 'pet_name',
        self::GAME_SETTINGS => 'game_settings',
        self::HIDE_GUILD_TAG => 'hide_gtag',
        self::FRIEND_REQUESTS => 'friend_requests',
        self::PARTY_REQUESTS => 'party_requests',
        self::FPS_MODE => 'low_fps',
        self::BOSS_BAR => 'boss_bar_enabled',
        self::TITAN_EXPIRE => 'titan_expire',
        self::CHAT_COLOR => 'chat_color',
        self::DMS_STATUS => 'dms_status',
        self::COINS => 'coins',
        self::SELECTED_RANK => 'selected_rank',
        self::HIDE_NICK_SKIN => 'hide_nick_skin',
        self::DISCORD_BOOST_ID => 'boost_id',
        self::ANNOUNCEMENTS => 'announcements_enabled',
        self::GAME_CONFIGURATIONS => 'game_configurations',
        self::LOBBY_DISCOVERED_ZONES => 'discovered_zones',
        self::LOBBY_COLLECTED_TOKENS => 'discovered_tokens',
        self::RANKED_CHAT => 'ranked_chat_enabled',
        self::GLOBAL_CHAT => 'global_chat_enabled',
        self::STAFF_NOTIFICATIONS => 'staff_notifications',
        self::MFA_ENABLED => 'mfa_enabled',
        self::LAST_TIER_UP => 'last_tier_up',
    ];

    private const DATA_TYPES = [
        self::COSMETICS => self::ARRAY,
        self::HIDE_PLAYERS => self::INT,
        self::STORE_DATA => self::ARRAY,
        self::KEYS => self::INT,
        self::KNOCKBACK => self::BOOL,
        self::SETUP => self::BOOL,
        self::PERMISSIONS => self::ARRAY,
        self::PET => self::STRING,
        self::PET_NAME => self::STRING,
        self::RANK => self::ARRAY,
        self::STATUS_CREDITS => self::INT,
        self::VOTE_TIME => self::INT,
        self::GAME_SETTINGS => self::ARRAY,
        self::XP => self::INT,
        self::MFA_ENABLED => self::INT,
        self::LOCALE => self::STRING,
        self::RELATIONS => self::ARRAY,
        self::GUILD => self::INT,
        self::HIDE_GUILD_TAG => self::INT,
        self::FRIEND_REQUESTS => self::INT,
        self::PARTY_REQUESTS => self::INT,
        self::FPS_MODE => self::INT,
        self::BOSS_BAR => self::INT,
        self::TITAN_EXPIRE => self::INT,
        self::EXTRA_FRIENDS => self::INT,
        self::LAST_SERVER => self::STRING,
        self::TRACK => self::STRING,
        self::REPLAY => self::INT,
        self::CHAT_COLOR => self::INT,
        self::CHAT_TYPE => self::INT,
        self::DATA_LOADED => self::BOOL,
        self::OLD_PLAYER_NAME => self::STRING,
        self::FORMS => self::FLOAT,
        self::NICK => self::STRING,
        self::RANKTAG => self::STRING,
        self::VANISH => self::BOOL,
        self::VOTE_CHECK => self::BOOL,
        self::FORWARD => self::ARRAY,
        self::CHAT_RELAY => self::BOOL,
        self::WHISPER_RELAY => self::BOOL,
        self::REPLY_PLAYER => self::STRING,
        self::PRELOADED => self::BOOL,
        self::TRANSFER => self::BOOL,
        self::RECONNECT => self::BOOL,
        self::STAFF_NOTIFICATIONS => self::INT,
        self::REPORTS => self::BOOL,
        self::DMS_STATUS => self::INT,
        self::COINS => self::INT,
        self::SELECTED_RANK => self::STRING,
        self::HIDE_NICK_SKIN => self::INT,
        self::FIRST_JOIN => self::BOOL,
        self::DISCORD_BOOST_ID => self::STRING,
        self::DISCORD_BOOSTER => self::BOOL,
        self::GUILD_CHAT => self::BOOL,
        self::ANNOUNCEMENTS => self::INT,
        self::GAME_CONFIGURATIONS => self::ARRAY,
        self::LOBBY_DISCOVERED_ZONES => self::ARRAY,
        self::LOBBY_COLLECTED_TOKENS => self::ARRAY,
        self::OFFICIAL_ADDRESS => self::BOOL,
        self::RANKED_CHAT => self::INT,
        self::GLOBAL_CHAT => self::INT,
        self::LAST_JOINED => self::INT,
        self::FIRST_JOINED => self::INT,
        self::LAST_TIER_UP => self::INT,
        self::PROMPT_MFA => self::BOOL,
        self::ALLOW_TOUCH_QUEUEING => self::BOOL,
        self::MUTED => self::BOOL,
        self::CONNECTION_ID => self::STRING,
        self::PRE_TRANSFER => self::BOOL,
    ];

    //DATA TYPES
    public const ARRAY = 0;
    public const BOOL = 1;
    public const FLOAT = 2;
    public const INT = 3;
    public const STRING = 5;

    public const OLD_PLAYER_NAME = -1;

    public const DATA_LOADED = 0;

    //ONLINE DB DATA
    public const COSMETICS = 1;
    public const HIDE_NICK_SKIN = 2;
    public const HIDE_PLAYERS = 3;
    public const SELECTED_RANK = 4;
    public const STORE_DATA = 5;
    public const KEYS = 6;
    public const COINS = 7;
    public const KNOCKBACK = 8;
    public const ANNOUNCEMENTS = 9;
    public const PERMISSIONS = 10;
    public const PET = 11;
    public const PET_NAME = 12;
    public const GAME_SETTINGS = 13;
    public const RANK = 14;
    public const STATUS_CREDITS = 15;
    public const VOTE_TIME = 16;
    public const XP = 17;
    public const LOCALE = 18;
    public const MFA_ENABLED = 19;
    public const RELATIONS = 20;
    public const GUILD = 21;
    public const HIDE_GUILD_TAG = 22;
    public const FRIEND_REQUESTS = 23;
    public const RANKED_CHAT = 24;
    public const GLOBAL_CHAT = 25;
    public const PARTY_REQUESTS = 26;
    public const LAST_SERVER = 27;
    public const EXTRA_FRIENDS = 28;
    public const FPS_MODE = 29;
    public const LAST_JOINED = 30;
    public const LAST_TIER_UP = 31;
    public const FIRST_JOINED = 32;
    public const BOSS_BAR = 33;
    public const TITAN_EXPIRE = 34;
    public const CHAT_COLOR = 35;
    public const DMS_STATUS = 37;
    public const DISCORD_BOOST_ID = 38;

    //PUBLIC RUNTIME DATA
    public const GUILD_CHAT = 39;
    public const TRACK = 40;
    public const CHAT_TYPE = 41;
    public const NICK = 42;
    public const CHAT_RELAY = 43;
    public const WHISPER_RELAY = 44;
    public const REPLY_PLAYER = 45;
    public const RECONNECT = 46;
    public const REPLAY = 47;
    public const STAFF_NOTIFICATIONS = 48;
    public const REPORTS = 49;
    public const FORWARD = 50;
    public const DISCORD_BOOSTER = 51;
    public const GAME_CONFIGURATIONS = 52;
    public const LOBBY_DISCOVERED_ZONES = 53;
    public const LOBBY_COLLECTED_TOKENS = 54;
    public const ALLOW_TOUCH_QUEUEING = 55;
    public const MUTED = 56;

    //PRIVATE RUNTIME DATA
    // 61*64
    public const FORMS = 65;
    public const RANKTAG = 66;
    public const OFFICIAL_ADDRESS = 67;
    public const VANISH = 68;
    public const VOTE_CHECK = 69;
    public const PROMPT_MFA = 70;
    public const SETUP = 71;

    //BUNGEE DATA
    public const PRE_TRANSFER = 99;
    public const PRELOADED = 100;
    public const TRANSFER = 101;
    public const FIRST_JOIN = 102;
    public const CONNECTION_ID = 103;

    /** @var array */
    private array $playerData = [];
    /** @var array<string, array<int, bool>> */
    private array $toBeSaved = [];
    /** @var GameSettings */
    private GameSettings $gameSettings;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct($plugin);
        $this->gameSettings = new GameSettings($this);
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @return bool
     */
    public function getBool(Player|string $player, int $id): bool
    {
        return (bool)$this->getValue($player, $id);
    }

    /**
     * @return mixed
     */
    private function getValue(Player|string $player, int $id): mixed
    {
        if ($player instanceof Player) {
            if (!isset($this->playerData[$player->getName()][$id])) {
                $this->playerData[$player->getName()][$id] = $this->getDefaultValue($player, $id);
            }

            $player = $player->getName();
        }

        if (!isset($this->playerData[$player][$id])) {
            $this->playerData[$player][$id] = $this->getDefaultValue($player, $id);
        }

        return $this->playerData[$player][$id];
    }

    /**
     * @param Player|string|null $player
     * @param int $id
     * @return array|bool|int|string|null
     */
    public function getDefaultValue(Player|string|null $player, int $id): array|bool|int|string|null
    {
        if ($player instanceof Player) {
            switch ($id) {
                case self::LOCALE:
                    return $player->getLocale();
                case self::RANKTAG:
                    return TextFormat::YELLOW . $player->getName();
                case self::ALLOW_TOUCH_QUEUEING:
                    return $player instanceof NGPlayer && $player->getInputMode() === InputMode::TOUCHSCREEN;
            }
        }

        switch ($id) {
            case self::ANNOUNCEMENTS:
            case self::FRIEND_REQUESTS:
            case self::PARTY_REQUESTS:
            case self::CHAT_RELAY:
            case self::STAFF_NOTIFICATIONS:
            case self::REPORTS:
            case self::GUILD_CHAT:
            case self::GLOBAL_CHAT:
            case self::BOSS_BAR:
                return true;
            case self::RANKTAG:
                return TextFormat::YELLOW . $player;
            default:
                $data_type = self::DATA_TYPES[$id];

                if ($data_type === self::ARRAY) {
                    return [];
                }

                if ($data_type === self::BOOL) {
                    return false;
                }

                if ($data_type === self::INT || $data_type === self::FLOAT) {
                    return 0;
                }

                return '';
        }
    }

    /**
     * @phpstan-param callable(bool $success): void $callable
     */
    public function loadPlayerData(NetworkSession $session, callable $callable, ?PlayerSocialInfo $info = null): void
    {
        Utils::validateCallableSignature(function (bool $success): void {}, $callable);

        if ($info === null) {
            $playerInfo = $session->getPlayerInfo();
            if ($playerInfo instanceof XboxLivePlayerInfo) {
                $xuid = $playerInfo->getXuid();
                $playerName = $playerInfo->getUsername();
                $connectionId = null;
            } else {
                $session->disconnect('§o§l§eN§6G§r§7: §cPlease use play.nethergames.org to join the server.');
                $callable(false);
                return;
            }
        } else {
            $xuid = $info->playerIdentifier;
            $playerName = $info->playerName;
            $connectionId = $info->connectionId;
        }

        if ($this->getBool($playerName, self::PRELOADED) && $this->getBool($playerName, self::DATA_LOADED)) {
            $expectedConnId = $this->getString($playerName, self::CONNECTION_ID);

            // If the connectionId is null, we can assume that this is deployed in a development environment.
            if ($connectionId === null || $expectedConnId === $connectionId) {
                $this->getPlugin()->getLogger()->debug("Connection ID for $playerName matched. Got $connectionId");
                $callable(true);
                return;
            }
            $this->unsetValue($playerName);

            $this->getPlugin()->getLogger()->warning("Connection ID for $playerName does not match! Expecting $expectedConnId, got $connectionId");
        }

        Await::f2c(function () use ($session, $xuid, $connectionId, $playerName, $callable): Generator {
            MySQLCredentials::executeSelect('player.load', [
                'xuid' => $xuid,
                'player_name' => $playerName,
            ], yield, yield Await::REJECT);

            $rows = yield Await::ONCE;

            if ($this->getPlugin()->isDisabled() || !$session->isConnected()) {
                $callable(false);
                return;
            }

            // Adapted from libMMO plugin, preventing matched accounts from joining the server.
            // The following method are more reliable, thus preventing duplicate accounts from joining the server.

            // Using LEFT JOIN + UNION + RIGHT JOIN, the result is the expected output should be the same as follows:
            // Reference: https://stackoverflow.com/questions/4796872/how-can-i-do-a-full-outer-join-in-mysql

            // [OK] xuid == null && relatedXuid == null                          -> No account has been created for this player (Or the condition are table empty)
            // [OK] xuid != null && relatedXuid == null                          -> Player changed their name
            // [OK] xuid == null && relatedXuid != null                          -> Another player with the same username has already been registered. [Checked by the previous NGEssentials]
            // [OK] xuid != null && relatedXuid != null && relatedXuid != xuid   -> Another player with the same username but with an existing account has already been registered. [Not checked properly in previous NGEssentials]
            // [OK] xuid != null && relatedXuid != null && relatedXuid == xuid   -> The player account matched with the database.

            if ($exists = (count($rows) > 0)) {
                $data = $rows[0];
                if (($data['xuid'] === null && $data['relatedXuid'] !== null) || ($data['xuid'] !== null && $data['relatedXuid'] !== null && $data['relatedXuid'] !== $data['xuid'])) {
                    $session->disconnect("§o§l§eN§6G§r§7: §eThis username is already registered. Learn more at https://ngmc.co/registered.");
                    $callable(false);
                    return;
                }
            }

            if ($exists) {
                $playerManager = $this->getPlugin()->getPlayerManager();
                $row = $rows[0];

                if ($playerName !== $row['player']) {
                    // Make sure that if the player name changes before everything else.
                    // This is to make sure that all the relationship to this player name are changed first.
                    MySQLCredentials::executeChange('player.change_name', ['xuid' => $xuid, 'player' => $playerName], yield, yield Await::REJECT);
                    yield Await::ONCE;

                    $playerManager->onPlayerNameChange($playerName, $row['player']);
                }

                $this->setValue($playerName, self::PERMISSIONS, array_reduce(explode(',', $row['permissions']), static function (array $carry, string $permission): array {
                    $carry[$permission] = true;
                    return $carry;
                }, []));
                $this->setValue($playerName, self::RANK, explode(',', $row['rank']));
                $this->setValue($playerName, self::COSMETICS, $playerManager->getCosmeticHandler()->processLoading($row[$cosmeticsName = self::COLUMN_NAMES[self::COSMETICS]]), false, true);
                $this->setValue($playerName, self::GAME_SETTINGS, $this->getGameSettings()->processLoading($row[$gameSettingsName = self::COLUMN_NAMES[self::GAME_SETTINGS]]), false, true);

                unset($row['permissions'], $row[$cosmeticsName], $row[$gameSettingsName], $row['rank'], $row['player'], $row['relatedXuid']);
                foreach ($row as $index => $value) {
                    if (is_int($id = array_search($index, self::COLUMN_NAMES, true))) {
                        if (self::DATA_TYPES[$id] === self::ARRAY) {
                            try {
                                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                            } catch (JsonException) {
                                $value = [];
                            }
                        }

                        $this->setValue($playerName, $id, $value, false, true);
                    }
                }
            } else {
                $this->setValue($playerName, self::FIRST_JOIN, true);
            }

            if ($this->getValue($playerName, self::LAST_TIER_UP) === null) {
                $this->setValue($playerName, self::LAST_TIER_UP, time());
            }
            $this->setValue($playerName, self::CONNECTION_ID, $connectionId ?? "");
            $this->setValue($playerName, self::DATA_LOADED, true);
            $callable(true);
        }, catches: function (Throwable $error) use ($callable): void {
            $this->getPlugin()->getLogger()->logException($error);
            $callable(true);
        });
    }

    /**
     * @param mixed $value
     */
    public function setValue(Player|string $player, int $id, mixed $value, bool $forceSave = false, bool $load = false): void
    {
        if ($player instanceof Player) {
            $playerInstance = $player;
            $player = $player->getName();

            $ignoreLoaded = false;
        } else {
            $playerInstance = $this->getPlugin()->getServer()->getPlayerExact($player);

            $ignoreLoaded = true;
        }

        if (($validatedValue = $this->validateValue($id, $value)) === null) {
            if ($id !== self::DISCORD_BOOST_ID && $id !== self::LAST_TIER_UP) { //blame keith & dries for using null in the column
                $this->getPlugin()->getLogger()->alert('Invalid datatype for player ' . $player . ', id: ' . $id . '| value: ' . (string)$value);
            }

            $validatedValue = $this->getDefaultValue($playerInstance, $id);
        }

        if (isset($this->playerData[$player][$id]) && $this->playerData[$player][$id] === $validatedValue) {
            return;
        }

        $this->playerData[$player][$id] = $validatedValue;

        if (!$load && isset(self::COLUMN_NAMES[$id]) && ($this->getBool($player, self::DATA_LOADED) || $ignoreLoaded)) {
            if ($forceSave) {
                $this->saveValue($player, $id);
            } else {
                $this->toBeSaved[$player][$id] = true;
            }
        }
    }

    /**
     * @param int $id
     * @param float|array|bool|int|string|null $value
     * @return bool|int|null|array|string|float
     */
    public function validateValue(int $id, float|array|bool|int|string|null $value): float|array|bool|int|string|null
    {
        $data_type = self::DATA_TYPES[$id];

        if ($data_type === self::ARRAY && is_array($value)) {
            return $value;
        }

        if ($data_type === self::BOOL) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value) || is_int($value) || is_float($value)) {
                return (bool)$value;
            }
        }

        if ($data_type === self::FLOAT && (is_float($value) || is_int($value))) {
            return $value;
        }

        if ($data_type === self::INT) {
            if (is_int($value)) {
                return $value;
            }

            if (is_float($value) || is_bool($value)) {
                return (int)$value;
            }
        }

        if ($data_type === self::STRING && is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param string $player
     * @param int $id
     */
    public function saveValue(string $player, int $id): void
    {
        $columnName = self::COLUMN_NAMES[$id];

        $value = $this->getValue($player, $id);
        if (self::DATA_TYPES[$id] === self::ARRAY) {
            try {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                return;
            }
        }

        MySQLCredentials::executeChangeRaw('UPDATE player_data SET ' . $columnName . ' = ? WHERE player = ?', [$value, $player], function (int $affectedRows) use ($player, $id): void {
            unset($this->toBeSaved[$player][$id]);
        }, function (SqlError $error) use ($player, $id): void {
            $this->getPlugin()->getLogger()->logException($error);
            $this->getPlugin()->getLogger()->alert('Failed to save player data for ' . $player . ', id: ' . $id);

            $this->toBeSaved[$player][$id] = true;
        });

        if ($id < 0) {
            $this->unsetValue($player, $id);
        }
    }

    /**
     * @param string|Player $player
     * @param int $id
     */
    public function unsetValue(Player|string $player, int $id = -1): void
    {
        if ($player instanceof Player) {
            $player = $player->getName();
        }

        if ($id === -1) {
            unset($this->playerData[$player]);
        } else {
            unset($this->playerData[$player][$id]);
        }
    }

    public function getGameSettings(): GameSettings
    {
        return $this->gameSettings;
    }

    public function setData(string $player, array $data): void
    {
        for ($id = 0; $id <= PlayerData::LOBBY_COLLECTED_TOKENS; $id++) {
            unset($this->playerData[$player][$id]);
        }

        foreach ($data as $id => $value) {
            if (isset(self::DATA_TYPES[$id])) {
                $this->setValue($player, $id, $value, false, true);
            } else {
                $this->getPlugin()->getLogger()->alert('Invalid datatype for player ' . $player . ', id: ' . $id . '| value: ' . (string)$value);
                $this->playerData[$player][$id] = $value;
            }
        }

        $this->setValue($player, self::PRELOADED, true);
    }

    public function getData(Player $player): array
    {
        $array = $this->playerData[$player->getName()];

        for ($i = self::FORMS; $i <= self::FIRST_JOIN; $i++) {
            if (isset($array[$i])) {
                unset($array[$i]);
            }
        }

        unset($array[self::OLD_PLAYER_NAME]);

        return $array;
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @return float
     */
    public function getFloat(Player|string $player, int $id): float
    {
        return (float)$this->getValue($player, $id);
    }

    public function saveData(Player $player, bool $quit = false): void
    {
        if ($this->getBool($player, self::DATA_LOADED)) {
            $this->saveMySQL($player, $quit);
        }

        if ($quit) {
            $this->unsetValue($player);
        }
    }

    public function saveMySQL(Player $player, bool $quit = false): void
    {
        $playerName = $player->getName();

        if (count($beingSaved = $this->toBeSaved[$playerName] ?? []) > 0 || $quit) {
            $values = [];
            $columns = [];

            if ($quit && !$this->getBool($player, self::TRANSFER)) {
                $values[] = time();
                $columns[] = 'last_quit = ?';
            }

            foreach (array_keys($beingSaved) as $id) {
                $value = $this->getValue($player, $id);

                if (self::DATA_TYPES[$id] === self::ARRAY) {
                    try {
                        $value = json_encode($value, JSON_THROW_ON_ERROR);
                    } catch (JsonException $e) {

                    }
                }

                $values[] = $value;
                $columns[] = self::COLUMN_NAMES[$id] . ' = ?';
            }

            if (count($values) === 0) {
                return;
            }

            $values[] = $player->getXuid();

            MySQLCredentials::executeChangeRaw('UPDATE player_data SET ' . implode(', ', $columns) . ' WHERE xuid = ?', $values, function (int $affectedRows) use ($playerName): void {
                unset($this->toBeSaved[$playerName]);
            }, function (SqlError $error) use ($player): void {
                $this->getPlugin()->getLogger()->logException($error);
                $this->getPlugin()->getLogger()->alert('Failed to save player data for ' . $player->getName());
            });
        }
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @param int $addon
     * @param bool $forceSave
     * @return int
     */
    public function addInt(Player|string $player, int $id, int $addon = 1, bool $forceSave = true): int
    {
        $int = $this->getInt($player, $id) + $addon;

        if ($id === self::XP) {
            $previousLevel = (int)ExperienceUtils::getLevelFromXp($this->getInt($player, $id));
            $newLevel = (int)ExperienceUtils::getLevelFromXp($int);

            if ($newLevel > $previousLevel && $player instanceof NGPlayer) {
                $playerManager = $this->getPlugin()->getPlayerManager();
                $levelFormat = $playerManager->getLevelFormat($newLevel);

                $player->playSound("random.levelup");
                $player->sendMessage(TextFormat::colorize(
                    TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "LEVEL UP! " . TextFormat::RESET . TextFormat::GREEN . "You are now level " . $levelFormat . TextFormat::RESET . TextFormat::GREEN . "!"
                ));
                $player->sendTitle(TextFormat::colorize(TextFormat::RESET), TextFormat::colorize(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "LEVEL UP!"), 10, 30);
            }
        }

        if ($id === self::STATUS_CREDITS) {
            $rankManager = $this->getPlugin()->getPlayerManager()->getRankManager();
            $previousTier = $rankManager->getTierFromCredits($this->getInt($player, $id));
            $newTier = $rankManager->getTierFromCredits($int);

            if ($newTier !== null && $newTier !== $previousTier) {
                if ($player instanceof NGPlayer) {
                    $player->playSound("random.levelup");
                    $player->sendMessage(TextFormat::colorize(
                        TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "TIER UP! " . TextFormat::RESET . TextFormat::GREEN . "You are now tier " . ucfirst($newTier->getName()) . TextFormat::RESET . TextFormat::GREEN . "!"
                    ));

                    if ($previousTier !== null) {
                        $player->sendMessage(TextFormat::colorize(
                            TextFormat::GREEN . "From " . ucfirst($previousTier->getName()) . TextFormat::RESET . TextFormat::GREEN . " to " . ucfirst($newTier->getName()) . TextFormat::RESET . TextFormat::GREEN . "!"
                        ));
                    }

                    $lastTierUp = $this->getInt($player, self::LAST_TIER_UP);
                    $player->sendMessage(TextFormat::colorize(
                        TextFormat::GREEN . "Time for tier up: " . TextFormat::GOLD . DateUtils::formatDiff($lastTierUp)
                    ));

                    if (($nextTier = $rankManager->getNextTier($newTier)) !== null) {
                        $player->sendMessage(TextFormat::colorize(
                            TextFormat::GREEN . "Credits for next tier: " . TextFormat::BOLD . TextFormat::GOLD . number_format($nextTier->getCredits())
                        ));
                    }
                    $player->sendTitle(TextFormat::colorize(TextFormat::RESET), TextFormat::colorize(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "TIER UP!"), 10, 30);

                    $rankManager->updateNameTag($player);
                    $rankManager->updatePermissions($player);
                }

                $this->setValue($player, self::LAST_TIER_UP, time());
            }
        }

        // We have to save the value each time we want to add something. Because there are chances for the current data
        // to be replaced with an older value (i.e.: 1000 to 500) and we don't want that.
        $this->setValue($player, $id, $int, load: $forceSave);

        if (isset(self::COLUMN_NAMES[$id]) && $forceSave) {
            $columnName = self::COLUMN_NAMES[$id];
            $playerName = $player instanceof Player ? $player->getName() : $player;

            MySQLCredentials::executeChangeRaw('UPDATE player_data SET ' . $columnName . ' = ' . $columnName . ' + ? WHERE player = ?', [$addon, $playerName], function (int $affectedRows) use ($playerName, $id): void {
                unset($this->toBeSaved[$playerName][$id]);
            }, function (SqlError $error) use ($playerName, $id): void {
                $this->getPlugin()->getLogger()->logException($error);
                $this->getPlugin()->getLogger()->alert('Failed to save player data for ' . $playerName . ', id: ' . $id);
            });
        }

        return $int;
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @return int
     */
    public function getInt(Player|string $player, int $id): int
    {
        return (int)$this->getValue($player, $id);
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @return array
     */
    public function getArray(Player|string $player, int $id): array
    {
        return (array)$this->getValue($player, $id);
    }

    public function getPlayerByNick(string $nick): ?Player
    {
        $found = null;
        $nick = strtolower($nick);
        $delta = PHP_INT_MAX;

        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
            if (($playerNick = $this->getString($player, self::NICK)) !== '' && stripos($playerNick, $nick) === 0) {
                $curDelta = strlen($player->getName()) - strlen($nick);
                if ($curDelta < $delta) {
                    $found = $player;
                    $delta = $curDelta;
                }
                if ($curDelta === 0) {
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * @param string|Player $player
     * @param int $id
     * @return string
     */
    public function getString(Player|string $player, int $id): string
    {
        return (string)$this->getValue($player, $id);
    }
}
