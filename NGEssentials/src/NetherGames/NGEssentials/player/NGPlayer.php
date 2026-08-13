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

use libproxy\ProxyNetworkInterface;
use NetherGames\NGEssentials\events\NGPlayerAFKEvent;
use NetherGames\NGEssentials\events\PlayerInputChangeEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\type\TextType;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\queue\TickQueue;
use NetherGames\NGEssentials\utils\TextUtils;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerPostChunkSendEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;
use function explode;
use function microtime;
use function round;
use function str_contains;
use function strtoupper;

class NGPlayer extends Player
{
    public const AFK_SECONDS = 3 * 60;

    /** @var string|null */
    private ?string $language = null;
    /** @var Skin */
    private Skin $originalSkin;
    /** @var float */
    private float $lastMove;
    /** @var bool */
    private bool $armorInvisible = false;
    /** @var bool */
    private bool $energized = false;
    /** @var string[] */
    private array $rankTags = [];
    /** @var PermissionAttachment|null */
    private ?PermissionAttachment $attachment = null;
    /** @var bool */
    private bool $loaded = false;
    /** @var int */
    private int $joinTime;
    /** @var int */
    private int $inputMode;
    /** @var float */
    private float $lastPitch;
    /** @var float */
    private float $lastYaw;
    /** @var TickQueue */
    private TickQueue $cps;
    private int $tick = 0;
    /** @var ?string */
    private ?string $proxyId = null;
    /** @var bool */
    private bool $locatorBarEnabled = false;

    /**
     * @var true[]
     * @phpstan-var array<int, true>
     */
    private array $activeChunkGenerationRequests = [];
    /** @var true[] */
    private array $tickingChunks = [];

    public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, Location $spawnLocation, ?CompoundTag $namedtag)
    {
        parent::__construct($server, $session, $playerInfo, $authenticated, $spawnLocation, $namedtag);

        $this->lastPitch = $spawnLocation->getPitch();
        $this->lastYaw = $spawnLocation->getYaw();
        $this->lastMove = microtime(true);
        $this->inputMode = $playerInfo->getExtraData()['CurrentInputMode'];
        $this->joinTime = time();
        $this->cps = new TickQueue();

        $this->setHealthTag(false);
    }

    public function setHealthTag(bool $bool = true): void
    {
        if ($bool) {
            $health = $this->getHealth();
            if ($health < 0.2) {
                $this->setScoreTag('§f§l0.1 §r' . CustomIcon::HEART);
            } else {
                $this->setScoreTag('§f§l' . round($health / 2, 1) . ' §r' . CustomIcon::HEART);
            }
        } elseif (NGEssentials::getInstance()->getServerManager()->enableCombatLogger()) {
            $this->setScoreTag('§f§l' . ($this->getInputIcon() ?? '') . ' ' . $this->getInputName());
        } else {
            $this->setScoreTag('');
        }
    }

    private function getInputIcon(): ?string
    {
        return match ($this->getInputMode()) {
            InputMode::MOUSE_KEYBOARD => CustomIcon::KEYBOARD,
            InputMode::TOUCHSCREEN => CustomIcon::TOUCH,
            InputMode::GAME_PAD => CustomIcon::CONTROLLER,
            default => null
        };
    }

    public function getInputMode(): int
    {
        return $this->inputMode;
    }

    public function getInputName(): string
    {
        return match ($this->getInputMode()) {
            InputMode::MOUSE_KEYBOARD => 'Mouse & Keyboard',
            InputMode::TOUCHSCREEN => 'Touch',
            InputMode::GAME_PAD => 'Controller',
            InputMode::MOTION_CONTROLLER => 'Motion Controller',
            default => 'Unknown'
        };
    }

    /**
     * @param string $playerName
     * @param callable $callable function(bool)
     */
    public static function doesNameExist(string $playerName, callable $callable): void
    {
        MySQLCredentials::executeSelect('player.name_exists', ['player' => $playerName], static function (array $rows) use ($callable) {
            $callable(count($rows) > 0);
        });
    }

    /**
     * @param string $player
     * @param callable $callable function(?string, ?string) RETURNS CORRECT CASED NAME
     */
    public static function getXuidByName(string $player, callable $callable): void
    {
        MySQLCredentials::executeSelect('player.get_xuid', ['player' => $player], static function (array $rows) use ($callable) {
            $callable($rows[0]['xuid'] ?? null, $rows[0]['player'] ?? null);
        });
    }

    public static function getNameByXuid(string $xuid, callable $callable): void
    {
        MySQLCredentials::executeSelect('player.get_name', ['xuid' => $xuid], static function (array $rows) use ($callable) {
            $callable($rows[0]['player'] ?? null);
        });
    }

    public static function getNameByXuidList(array $xuidList, callable $callable): void
    {
        if (empty($xuidList)) {
            $callable([]);
            return;
        }

        $values = [];
        $columns = [];

        foreach ($xuidList as $xuid) {
            $columns[] = 'xuid = ?';
            $values[] = "$xuid";
        }

        MySQLCredentials::executeSelectRaw("SELECT player, xuid FROM player_data WHERE " . implode(' OR ', $columns), $values, static function (array $rows) use ($callable) {
            $callable($rows);
        });
    }

    public function getPermissionAttachment(): PermissionAttachment
    {
        return $this->attachment ??= $this->addAttachment(NGEssentials::getInstance());
    }

    public function teleport(Vector3 $pos, ?float $yaw = null, ?float $pitch = null): bool
    {
        if (!($pos instanceof Position) || $this->getWorld() === $pos->getWorld()) {
            return parent::teleport($pos, $yaw, $pitch);
        }

        // If the player is teleporting to a different world, we need to send the dimension change packet

        $networkSession = $this->getNetworkSession();
        $playerData = NGEssentials::getInstance()->getPlayerData();
        if ($this->spawned && !$playerData->getBool($this, PlayerData::PRE_TRANSFER) && !$playerData->getBool($this, PlayerData::TRANSFER) && $networkSession->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_60) {
            $this->setNoClientPredictions(true);
            $this->sendDimension(DimensionIds::NETHER, $pos->add(2000, 0, 2000), true);
            $networkSession->syncMovement($pos, $this->lastYaw, $this->lastPitch, MovePlayerPacket::MODE_TELEPORT);
            $networkSession->sendDataPacket(PlayStatusPacket::create(PlayStatusPacket::PLAYER_SPAWN), true);
            $this->sendDimension(DimensionIds::OVERWORLD, $this->getLocation(), true);

            $this->spawnChunkLoadCount = 0;
        }

        return parent::teleport($pos, $yaw, $pitch);
    }

    /**
     * @phpstan-param DimensionIds::* $dimensionId
     */
    private function sendDimension(int $dimensionId, Vector3 $pos, bool $chunks = true): void
    {
        $session = $this->getNetworkSession();
        $session->sendDataPacket(ChangeDimensionPacket::create($dimensionId, $pos, true, null));
        $session->sendDataPacket(StopSoundPacket::create('portal.travel', true, false));

        if ($chunks) {
            $emptyChunk = ChunkSerializer::serializeFullChunk(
                $chunk = new Chunk([], true),
                $dimensionId,
                $session->getTypeConverter(),
            );
            $subChunkCount = ChunkSerializer::getSubChunkCount($chunk, $dimensionId);

            $session->syncViewAreaCenterPoint($pos, $this->viewDistance);

            $chunkX = $pos->getFloorX() >> 4;
            $chunkZ = $pos->getFloorZ() >> 4;

            for ($X = $chunkX - $this->viewDistance; $X <= $chunkX + $this->viewDistance; ++$X) {
                for ($Z = $chunkZ - $this->viewDistance; $Z <= $chunkZ + $this->viewDistance; ++$Z) {
                    $session->sendDataPacket(LevelChunkPacket::create(
                        new ChunkPosition($X, $Z),
                        $dimensionId,
                        $subChunkCount,
                        false,
                        null,
                        $emptyChunk
                    ));
                }
            }
        }

        $session->sendDataPacket(PlayerActionPacket::create(
            $this->getId(),
            PlayerAction::DIMENSION_CHANGE_ACK,
            $nullPos = BlockPosition::fromVector3(Vector3::zero()),
            $nullPos,
            0
        ), true);
    }

    /**
     * Requests chunks from the world to be sent, up to a set limit every tick. This operates on the results of the most recent chunk
     * order.
     */
    protected function requestChunks(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        Timings::$playerChunkSend->startTiming();

        $count = 0;
        $world = $this->getWorld();

        $limit = $this->chunksPerTick - count($this->activeChunkGenerationRequests);
        foreach ($this->loadQueue as $index => $distance) {
            if ($count >= $limit) {
                break;
            }

            World::getXZ($index, $X, $Z);

            ++$count;

            $this->usedChunks[$index] = UsedChunkStatus::REQUESTED_GENERATION;
            $this->activeChunkGenerationRequests[$index] = true;
            unset($this->loadQueue[$index]);
            $world->registerChunkLoader($this->chunkLoader, $X, $Z, true);
            $world->registerChunkListener($this, $X, $Z);
            if (isset($this->tickingChunks[$index])) {
                $world->registerTickingChunk($this->chunkTicker, $X, $Z);
            }

            $world->requestChunkPopulation($X, $Z, $this->chunkLoader)->onCompletion(
                function () use ($X, $Z, $index, $world): void {
                    if (!$this->isConnected() || !isset($this->usedChunks[$index]) || $world !== $this->getWorld()) {
                        return;
                    }
                    if ($this->usedChunks[$index] !== UsedChunkStatus::REQUESTED_GENERATION) {
                        //We may have previously requested this, decided we didn't want it, and then decided we did want
                        //it again, all before the generation request got executed. In that case, the promise would have
                        //multiple callbacks for this player. In that case, only the first one matters.
                        return;
                    }
                    unset($this->activeChunkGenerationRequests[$index]);
                    $this->usedChunks[$index] = UsedChunkStatus::REQUESTED_SENDING;

                    $this->getNetworkSession()->startUsingChunk($X, $Z, function () use ($X, $Z, $index): void {
                        $this->usedChunks[$index] = UsedChunkStatus::SENT;

                        if ($this->spawnChunkLoadCount === -1) {
                            $this->spawnEntitiesOnChunk($X, $Z);
                        } elseif ($this->spawnChunkLoadCount++ === $this->spawnThreshold) {
                            $this->spawnChunkLoadCount = -1;

                            $this->spawnEntitiesOnAllChunks();

                            $session = $this->getNetworkSession();
                            if ($this->spawned) {
                                $session->sendDataPacket(PlayStatusPacket::create(PlayStatusPacket::PLAYER_SPAWN));
                                $this->setNoClientPredictions(false);
                            } else {
                                $session->notifyTerrainReady();
                            }
                        }
                        (new PlayerPostChunkSendEvent($this, $X, $Z))->call();
                    });
                },
                static function (): void {
                    //NOOP: we'll re-request this if it fails anyway
                }
            );
        }

        Timings::$playerChunkSend->stopTiming();
    }

    /**
     * @return string[]
     */
    public function getRankTags(): array
    {
        return $this->rankTags;
    }

    /**
     * @param string[] $tags
     * @internal
     */
    public function setRankTags(array $tags): void
    {
        $this->rankTags = $tags;
    }

    public function getLastMoveTime(): float
    {
        return $this->lastMove;
    }

    public function getOriginalSkin(): Skin
    {
        return $this->originalSkin;
    }

    public function setOriginalSkin(Skin $skin): void
    {
        $this->originalSkin = $skin;
    }

    public function spawnToAll(): void
    {
        if ($this->isLoaded()) {
            parent::spawnToAll();
        }
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function setLoaded(): void
    {
        $this->loaded = true;
    }

    public function getNGLanguage(): string
    {
        return $this->language ?? Translator::FALLBACK_LANGUAGE;
    }

    public function setNGLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function playSound(string $sound, float $volume = 1.0, float $pitch = 1.0): void
    {
        $location = $this->getLocation();

        $this->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            $sound,
            $location->x,
            $location->y,
            $location->z,
            $volume,
            $pitch,
            null
        ));
    }

    public function getProxyRegion(): string
    {
        return ($proxyId = $this->getProxyId()) === null ? ServerManager::REGION_US : strtoupper(explode('-', $proxyId)[0]);
    }

    public function getProxyId(): ?string
    {
        return $this->proxyId;
    }

    public function setProxyId(string $proxyId): void
    {
        $this->proxyId = $proxyId;
    }

    public function isArmorInvisible(): bool
    {
        return $this->armorInvisible;
    }

    public function setArmorInvisible(bool $value = true): void
    {
        $this->armorInvisible = $value;
    }

    public function sendTitle(string $title, string $subtitle = '', int $fadeIn = 0, int $stay = 40, int $fadeOut = 0): void
    {
        parent::sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
    }

    /**
     * Sends player a message as a popup message if the player has it enabled in their settings
     *
     * @param string $message Array of strings and ints to send if a title
     * @param int $type       Message type (@see TextType)
     * @return void
     */
    public function sendConditionalMessage(string $message, int $type = TextType::TYPE_ACTIONBAR): void
    {
        if (NGEssentials::getInstance()->getPlayerData()->getGameSettings()->getBool($this, GameSettings::POPUP_MESSAGES) && $type !== TextType::TYPE_CHAT) {
            $centeredMessage = TextUtils::center($message);
            if ($type === TextType::TYPE_ACTIONBAR) {
                $this->sendActionBarMessage($centeredMessage);
            } elseif ($type === TextType::TYPE_POPUP) {
                $this->sendPopup($centeredMessage);
            } elseif ($type === TextType::TYPE_TIP) {
                $this->sendTip($centeredMessage);
            } elseif ($type === TextType::TYPE_JUKEBOX_POPUP) {
                $this->sendJukeboxPopup($centeredMessage);
            } elseif ($type === TextType::TYPE_TITLE) {
                $this->sendTitle($centeredMessage);
            } elseif ($type === TextType::TYPE_TOAST) {
                $this->sendToastNotification($centeredMessage, $message);
            } else {
                $this->sendMessage($message);
                $this->sendMessage(TextFormat::RED . "Error: Invalid message type: $type. Please report this to a staff member.");
            }
        } else {
            $this->sendMessage($message);
        }
    }

    public function setHealth(float $amount): void
    {
        parent::setHealth($amount);

        if ($this->getHealthTag() && $this->isAlive()) {
            $this->updateHealthTag();
        }
    }

    public function getHealthTag(): bool
    {
        return str_contains($this->getScoreTag(), CustomIcon::HEART);
    }

    public function isEnergized(): bool
    {
        return $this->energized;
    }

    public function setEnergized(bool $value = true): void
    {
        $this->energized = $value;

        if ($value) {
            $hungerManager = $this->getHungerManager();
            $hungerManager->setFood($hungerManager->getMaxFood());
            $hungerManager->setExhaustion(0.0);
        }
    }

    public function setLocatorBarEnabled(bool $value = true): void
    {
        if ($this->locatorBarEnabled !== $value) {
            $this->locatorBarEnabled = $value;
            if ($this->getNetworkSession()->getProtocolId() >= ProtocolInfo::PROTOCOL_1_21_90) {
                $this->toggleGameRule('locatorbar', $value);
            }
        }
    }

    public function isLocatorBarEnabled(): bool
    {
        return $this->locatorBarEnabled;
    }

    public function toggleGameRule(string $gameRule, bool $toggle): void
    {
        $this->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
            $gameRule => new BoolGameRule($toggle, false)
        ]));
    }

    public function getDeviceOS(): string
    {
        return match ($this->getNetworkSession()->getPlayerInfo()?->getExtraData()['DeviceOS'] ?? null) {
            DeviceOS::ANDROID => 'Android',
            DeviceOS::IOS => 'iOS',
            DeviceOS::OSX => 'macOS',
            DeviceOS::AMAZON => 'FireOS',
            DeviceOS::GEAR_VR => 'GearVR',
            DeviceOS::HOLOLENS => 'HoloLens',
            DeviceOS::WINDOWS_10 => 'Windows 10',
            DeviceOS::WIN32 => 'Windows',
            DeviceOS::DEDICATED => 'Dedicated',
            DeviceOS::TVOS => 'tvOS',
            DeviceOS::PLAYSTATION => 'PlayStation',
            DeviceOS::NINTENDO => 'Nintendo',
            DeviceOS::XBOX => 'Xbox',
            DeviceOS::WINDOWS_PHONE => 'Windows Phone',
            default => 'Unknown'
        };
    }

    public function getDeviceModel(): string
    {
        return $this->getNetworkSession()->getPlayerInfo()?->getExtraData()['DeviceModel'] ?? 'Unknown';
    }

    public function getDeviceId(): string
    {
        return $this->getNetworkSession()->getPlayerInfo()?->getExtraData()['DeviceId'];
    }

    public function getUI(): int
    {
        return $this->getNetworkSession()->getPlayerInfo()?->getExtraData()['UIProfile'];
    }

    public function getGUIScale(): int
    {
        return $this->getNetworkSession()->getPlayerInfo()?->getExtraData()['GuiScale'];
    }

    public function getLatencyData(): array
    {
        if ($this->proxyId !== null) { // uses WDPE proxy
            $latencyData = ProxyNetworkInterface::getLatencyData($this->getNetworkSession());

            if ($latencyData !== null) {
                return [$latencyData->getDownstream(), $latencyData->getUpstream()];
            }
        }

        return [0, $this->getNetworkSession()->getPing() ?? 0];
    }

    /**
     * Sends a message to the receivers as this player
     *
     * @param Player[] $receivers
     */
    public function sendChat(string $message, array $receivers): void
    {
        $packet = TextPacket::raw($message);
        $packet->type = TextPacket::TYPE_CHAT;
        $packet->sourceName = "";
        $packet->xboxUserId = $this->getXuid();

        foreach ($receivers as $receiver) {
            $receiver->getNetworkSession()->sendDataPacket($packet);
        }
    }

    public function getJoinTime(): int
    {
        return $this->joinTime;
    }

    public function handlePlayerAuthInput(PlayerAuthInputPacket $packet): void
    {
        $pitch = $packet->getPitch();
        $yaw = $packet->getYaw();

        if (($rotation = Utils::rotationDifference(
                $this->lastPitch,
                $this->lastYaw,
                $pitch,
                $yaw
            ))[Utils::ROTATION_INDEX_PITCH] > 0.01 || $rotation[Utils::ROTATION_INDEX_YAW] > 0.01
        ) {
            $this->lastMove = microtime(true);
        }

        $this->lastPitch = $pitch;
        $this->lastYaw = $yaw;
        $this->tick = $tick = $packet->getTick();
        $this->cps->filterAfterTick($tick - 19);

        if ($this->inputMode !== ($inputMode = $packet->getInputMode())) {
            $event = new PlayerInputChangeEvent($this, $this->inputMode, $inputMode);
            $event->call();

            $this->inputMode = $inputMode;

            if (!$this->getHealthTag()) {
                $this->updateHealthTag();
            }
        }
    }

    public function updateHealthTag(): void
    {
        $this->setHealthTag($this->getHealthTag());
    }

    public function getCPS(): int
    {
        return $this->cps->size();
    }

    public function handleInventoryTransaction(InventoryTransactionPacket $packet): void
    {
        $trData = $packet->trData;

        if ($trData instanceof UseItemOnEntityTransactionData) {
            if ($trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $this->cps->enqueue($this->tick);
            }
        }
    }

    public function handleLevelSound(LevelSoundEventPacket $packet): void
    {
        if ($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE) {
            $this->cps->enqueue($this->tick);
        }
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ((microtime(true) - $this->lastMove) > self::AFK_SECONDS) {
            $this->lastMove = microtime(true);

            $event = new NGPlayerAFKEvent($this);
            $event->call();
        }

        return parent::entityBaseTick($tickDiff);
    }
}
