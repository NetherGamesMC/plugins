<?php

declare(strict_types=1);


namespace libReplay\session\record;


use Closure;
use libReplay\Replays;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use function json_encode;
use function time;
use const JSON_THROW_ON_ERROR;

class RecordManager
{
    public const DATA_MAP_NAME = 0;
    public const DATA_PLAYERS = 1;
    public const DATA_PRIVATE = 2;
    public const DATA_TOUCH_ONLY = 3;

    /** @var self|null */
    private static ?self $instance = null;
    /** @var CameraFactory */
    private CameraFactory $cameraFactory;
    /** @var Replays */
    private Replays $manager;
    /** @var Recording[] */
    private array $recordings = [];

    public function __construct(Replays $manager)
    {
        $sessionManager = $manager->getPlugin()->getServer()->getNetwork()->getSessionManager();
        $this->cameraFactory = new CameraFactory($sessionManager);
        $this->manager = $manager;

        $plugin = $manager->getPlugin();
        $plugin->getServer()->getPluginManager()->registerEvents(new RecordListener($this), $plugin);

        self::$instance = $this;
    }

    /**
     * @param array<int, mixed> $extraInfo
     * @param Closure|null $callback function(?Recording $recording): void
     */
    public function startRecording(World $world, array $extraInfo, ?Closure $callback = null): void
    {
        if ($callback !== null) {
            Utils::validateCallableSignature(function (?Recording $recording): void {}, $callback);
        }

        $serverManager = NGEssentials::getInstance()->getServerManager();

        $serverType = $serverManager->getServerType();
        $gameType = $serverManager->getGameType();

        MySQLCredentials::executeInsert('replay.create', [
            'server_type' => $serverType,
            'game_type' => $gameType,
            'private' => ($extraInfo[RecordManager::DATA_PRIVATE] ?? false) ? 1 : 0,
            'touch_only' => ($extraInfo[RecordManager::DATA_TOUCH_ONLY] ?? false) ? 1 : 0,
            'map_name' => $extraInfo[RecordManager::DATA_MAP_NAME] ?? '',
            'players' => json_encode($extraInfo[RecordManager::DATA_PLAYERS] ?? [], JSON_THROW_ON_ERROR),
            'protocol_id' => ProtocolInfo::CURRENT_PROTOCOL,
            'time' => time()
        ], function (int $id) use ($world, $callback) {
            if ($world->isLoaded()) {
                $this->recordings[$world->getId()] = new Recording($world, $this->cameraFactory, $id);
            }

            if ($callback !== null) {
                $callback($this->recordings[$world->getId()] ?? null);
            }
        });
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function getManager(): Replays
    {
        return $this->manager;
    }

    public function stopRecording(World $world, bool $save = true): void
    {
        if (($recording = $this->getRecording($world)) !== null) {
            if ($save) {
                $sender = $recording->getCamera()->getFilmroll()->getSender();
                $sender->setSaving($save);
            }

            $recording->stop();

            unset($this->recordings[$world->getId()]);
        }
    }

    public function getRecording(World $world): ?Recording
    {
        return $this->recordings[$world->getId()] ?? null;
    }

    /**
     * @return Recording[]
     */
    public function getRecordings(): array
    {
        return $this->recordings;
    }
}