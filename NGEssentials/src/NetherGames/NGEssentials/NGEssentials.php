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
use Grpc\Call;
use libasynCurl\Curl;
use libforms\FormManager;
use libVanilla\VanillaPlugin;
use NetherGames\NGEssentials\block\CustomBlockRegistry;
use NetherGames\NGEssentials\commands\BaseCommand;
use NetherGames\NGEssentials\elasticsearch\ElasticSearch;
use NetherGames\NGEssentials\entity\EntityManager;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\kafka\KafkaConsumer;
use NetherGames\NGEssentials\kafka\KafkaPublisher;
use NetherGames\NGEssentials\kafka\KafkaUtility;
use NetherGames\NGEssentials\player\combatlogger\CombatLogger;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\social\guilds\objects\Guild;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\player\utils\TournamentManager;
use NetherGames\NGEssentials\tasks\BlockRegisterAsyncTask;
use NetherGames\NGEssentials\tasks\ComposerRegisterAsyncTask;
use NetherGames\NGEssentials\tasks\ElasticSearchRegisterAsyncTask;
use NetherGames\NGEssentials\tasks\ThreadPoolCollectorTask;
use NetherGames\NGEssentials\tasks\ThreadPoolGCTask;
use NetherGames\NGEssentials\thread\NGThreadPool;
use NetherGames\NGEssentials\utils\Credentials;
use NetherGames\NGEssentials\utils\ErrorLogger;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\ParticleOptimizer;
use NetherGames\NGEssentials\utils\skins\SkinValidatorAdapter;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use Symfony\Component\Filesystem\Path;
use Throwable;
use function date_default_timezone_set;
use function is_file;
use function pocketmine\critical_error;
use const nethergames\COMPOSER_AUTOLOADER_PATH;

require_once 'CoreConstants.php';

class NGEssentials extends PluginBase
{
    /** @var bool */
    private static bool $developmentMode = false;
    /** @var bool */
    private static bool $hybridDevelopmentMode = false;
    /** @var NGEssentials|null */
    private static ?NGEssentials $instance = null;

    /** @var ErrorLogger */
    private ErrorLogger $errorLogger;
    /** @var ServerManager */
    private ServerManager $serverManager;
    /** @var ServerData */
    private ServerData $serverData;
    /** @var PlayerManager */
    private PlayerManager $playerManager;
    /** @var PlayerData */
    private PlayerData $playerData;
    /** @var TournamentManager */
    private TournamentManager $tournamentManager;
    /** @var EntityManager */
    private EntityManager $entityManager;
    /** @var CombatLogger */
    private CombatLogger $combatLogger;
    /** @var ?KafkaPublisher */
    private ?KafkaPublisher $publisher = null;
    /** @var ?KafkaConsumer */
    private ?KafkaConsumer $consumer = null;

    public static function getLogo(): string
    {
        return TextFormat::BOLD . TextFormat::YELLOW . 'Nether' . TextFormat::GOLD . 'Games ';
    }

    public function onLoad(): void
    {
        date_default_timezone_set('UTC');

        if (is_file(COMPOSER_AUTOLOADER_PATH)) {
            require_once(COMPOSER_AUTOLOADER_PATH);
        } else {
            critical_error("Composer autoloader not found at " . COMPOSER_AUTOLOADER_PATH);
            critical_error("Please install/update Composer dependencies or use provided builds.");

            $this->getServer()->shutdown();
            return;
        }

        self::$developmentMode = (bool)$this->getConfig()->getNested('developmentMode') === true;
        self::$hybridDevelopmentMode = self::$developmentMode && (bool)$this->getConfig()->getNested('hybridDevelopmentMode') === true;
        self::$instance = $this;

        $this->errorLogger = new ErrorLogger();
        if (!self::$developmentMode) {
            $this->getServer()->getLogger()->addAttachment($this->getErrorLogger());
        }

        $this->saveResource(Path::join('credentials', 'credentials.yml'));
        Credentials::sendCredentials($this, new Config(Path::join($this->getDataFolder(), 'credentials', 'credentials.yml'), Config::YAML));
        $this->getLogger()->info('§aImported all credentials successfully!');

        // This is to make sure that the credentials uses the environment variable first instead of the config file.
        if (getenv("DEVELOPMENT_MODE") !== false) {
            self::$developmentMode = true;
            if (getenv("HYBRID_MODE") !== false) {
                self::$hybridDevelopmentMode = true;
            }
        }

        new NGThreadPool(NGThreadPool::NG_POOL_SIZE, NGThreadPool::NG_MEMORY_LIMIT, $this->getServer()->getLoader(), $this->getServer()->getLogger(), $this->getServer()->getTickSleeper());
        $this->getScheduler()->scheduleRepeatingTask(new ThreadPoolCollectorTask(), 1);
        $this->getScheduler()->scheduleDelayedRepeatingTask(new ThreadPoolGCTask(), 15 * 60 * 20, 15 * 60 * 20);

        $this->getLogger()->info('§aThread pool initiated successfully!');

        $this->serverData = new ServerData($this);
        $this->serverManager = new ServerManager($this);
        $this->getLogger()->info('§aServerManager initiated successfully!');

        new ElasticSearch($this);
        //$this->getServer()->getLogger()->addAttachment(new ElasticSearchLoggerAttachment($elasticSearch));

        $this->getServer()->getAsyncPool()->addWorkerStartHook($this->getStartupHook($this->getServer()->getAsyncPool()));
        NGThreadPool::getInstance()->addWorkerStartHook($this->getStartupHook(NGThreadPool::getInstance()));

        if (!NGEssentials::isInDevelopmentMode() && !NGEssentials::isInHybridDevelopmentMode()) {
            $regionEU = getenv("EU_KAFKA_HOST") !== false ? getenv("EU_KAFKA_HOST") : "eu-kafka.infra-prod.svc:9092";
            $regionUS = getenv("US_KAFKA_HOST") !== false ? getenv("US_KAFKA_HOST") : "us-kafka.infra-prod.svc:9092";
            $regionIN = getenv("IN_KAFKA_HOST") !== false ? getenv("IN_KAFKA_HOST") : "ap-kafka.infra-prod.svc:9092";

            KafkaUtility::setKafkaEndpoint(match ($this->getServerManager()->getServerRegion()) {
                ServerManager::REGION_US => "$regionUS,$regionEU,$regionIN",
                ServerManager::REGION_EU => "$regionEU,$regionUS,$regionIN",
                default => "$regionIN,$regionEU,$regionUS",
            });
        }

        if ((bool)$this->getConfig()->getNested('kafkaEnabled', true) === true) {
            $kafkaConsumer = new KafkaConsumer($this->getServer()->getTickSleeper(), COMPOSER_AUTOLOADER_PATH, $this->getServer()->getLogger(), [
                'client.id' => "Essentials/Consumer",
                'group.id' => $this->getServerManager()->getUniqueId(),
                'auto.offset.reset' => 'latest'
            ]);
            $kafkaConsumer->start();

            $this->getScheduler()->scheduleRepeatingTask($kafkaPublisher = new KafkaPublisher([
                'client.id' => "Essentials/Publisher",
            ]), 2);

            $this->consumer = $kafkaConsumer;
            $this->publisher = $kafkaPublisher;

            $this->getLogger()->info('§aKafka Consumer/Producer initialized successfully.');
        } else {
            $this->getLogger()->info('§cKafka Consumer/Producer disabled in config.');
        }

        Curl::register($this);
        ParticleOptimizer::setInstance(new ParticleOptimizer($this));

        VanillaPlugin::MISSING_VANILLA_BLOCKS()->register($this);

        CustomItemRegistry::forceInitialization();
        CustomBlockRegistry::forceInitialization();

        $this->entityManager = new EntityManager($this);
    }

    /**
     * @phpstan-return Closure(int $workerId) : void
     */
    public function getStartupHook(AsyncPool $asyncPool): Closure
    {
        $elasticSearch = ElasticSearch::getInstance();

        return function (int $workerId) use ($elasticSearch, $asyncPool): void {
            $asyncPool->submitTaskToWorker(new ComposerRegisterAsyncTask(), $workerId);
            $asyncPool->submitTaskToWorker(new ElasticSearchRegisterAsyncTask($elasticSearch), $workerId);
            $asyncPool->submitTaskToWorker(new BlockRegisterAsyncTask(), $workerId);
        };
    }

    public function getErrorLogger(): ErrorLogger
    {
        return $this->errorLogger;
    }

    /**
     * @return NGEssentials
     * @throws RuntimeException
     */
    public static function getInstance(): NGEssentials
    {
        if (self::$instance === null) {
            throw new RuntimeException("Attempt to retrieve NGThreadPool instance outside server thread");
        }
        return self::$instance;
    }

    public static function isInDevelopmentMode(): bool
    {
        return self::$developmentMode;
    }

    public static function isInHybridDevelopmentMode(): bool
    {
        return self::$hybridDevelopmentMode;
    }

    public function getServerManager(): ServerManager
    {
        return $this->serverManager;
    }

    public function onEnable(): void
    {
        $serverManager = $this->getServerManager();
        $serverType = $serverManager->getServerType();

        $this->tournamentManager = new TournamentManager($this);
        $this->playerData = new PlayerData($this);
        $this->playerManager = new PlayerManager($this);
        Translator::init();

        if ($this->getServerManager()->enableCombatLogger()) {
            $this->combatLogger = new CombatLogger($this);
        }

        new FormManager($this);

        $this->getLogger()->info('§aClasses registered successfully!');

        BaseCommand::registerCommands($this);
        $this->getLogger()->info('§aCommands registered successfully!');

        foreach (TypeConverter::getAll() as $converter) {
            $converter->setSkinAdapter(new SkinValidatorAdapter());
        }

        TypeConverter::addCreationListener(fn(TypeConverter $converter) => $converter->setSkinAdapter(new SkinValidatorAdapter()));

        $server = $this->getServer();
        $server->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info('§aEvents registered successfully!');

        $worldManager = $server->getWorldManager();
        if ($serverManager->isMMOGame()) {
            $worldManager->setAutoSave(true);
        } else {
            if (self::isInDevelopmentMode()) {
                $worldManager->setAutoSave(true);
            } else {
                $worldManager->setAutoSave($serverType === ServerManager::CREATIVE);
            }

            /** @var World $defaultWorld */
            $defaultWorld = $worldManager->getDefaultWorld();
            $defaultWorld->stopTime();
        }
        $this->getLogger()->info('§aWorld settings have been set!');

        $this->getEntityManager()->onEnable();

        /** @phpstan-ignore-next-line */
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => Call::drainCompletionQueue(PHP_INT_MIN)), 1);

        if ($this->getServerManager()->getServerRegion() === ServerManager::REGION_US && $this->getServerManager()->getServerType() === ServerManager::LOBBY) {
            $this->startDBRecovery();
        }

        $this->getLogger()->info('§eNether§6Games §bEssentials enabled!');
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public static function isProxyEnabled(): bool
    {
        return (!self::isInDevelopmentMode() || self::isInHybridDevelopmentMode()) && !getenv("TCP_DISABLED");
    }

    /**
     * Database recovery system, an application-level database consistency check.
     * The 'guild_member' where role = 2 and 'guilds' tables must have the same total of rows.
     * This will make sure that both tables are in sync, but this is very highly unlikely to happen in the future.
     */
    private function startDBRecovery(): void
    {
        // Attempts to validate all guild data, this process may take a huge chunk of memory usage
        // However, they will be deleted when the process is completed.
        Await::f2c(function () {
            MySQLCredentials::executeSelectRaw("SELECT guild_id, leader FROM guilds", [], yield, yield Await::REJECT);
            $guildMembers = yield Await::ONCE;

            $orphaned = [];
            foreach ($guildMembers as $a) {
                $orphaned[$a['guild_id']] = $a['leader'];
            }

            $this->getLogger()->info(TextFormat::YELLOW . "Processed a total of " . count($orphaned) . " guilds.");

            MySQLCredentials::executeSelectRaw("SELECT guild_id FROM guild_members WHERE role = 2", [], yield, yield Await::REJECT);
            $guildMembers = yield Await::ONCE;

            foreach ($guildMembers as $a) {
                unset($orphaned[$a['guild_id']]);
            }

            if (empty($orphaned)) {
                $this->getLogger()->info(TextFormat::GREEN . "Database is currently healthy and validated.");
            } else {
                $this->getLogger()->info(TextFormat::RED . "Found " . count($orphaned) . " invalid guilds, starting recovery.");

                foreach ($orphaned as $guildId => $guildLeader) {
                    MySQLCredentials::executeSelectRaw("SELECT * FROM guild_members WHERE guild_id = ?", [
                        $guildId
                    ], yield, yield Await::REJECT);

                    $guildMembers = yield Await::ONCE;

                    MySQLCredentials::executeSelectRaw("SELECT * FROM guild_members WHERE player = ?", [
                        $guildLeader
                    ], yield, yield Await::REJECT);

                    $ownerResult = (yield Await::ONCE)[0] ?? [];

                    // If the table data is empty, we will try to search for the owner of this guild and if the
                    // guild owner have not joined any guild, we will try to recover it back to its original owner
                    // and if the owner joined a guild, we will delete this guild from our database.
                    if (empty($guildMembers)) {
                        if (empty($ownerResult)) {
                            MySQLCredentials::executeInsert("guild.add_member", [
                                'guild_id' => $guildId,
                                'role' => Guild::RANK_LEADER,
                                'player_name' => $guildLeader
                            ], yield, yield Await::REJECT);

                            $this->getLogger()->warning("Recovery method for guild with id='" . $guildId . "' is 0x1");
                        } else {
                            MySQLCredentials::executeChangeRaw("guild.delete", [
                                'guild_id' => $guildId
                            ], yield, yield Await::REJECT);

                            $this->getLogger()->warning("Recovery method for guild with id='" . $guildId . "' is 0x2");
                        }

                        yield Await::ONCE;

                        continue;
                    }

                    $playerName = []; // player => guild_id

                    foreach ($guildMembers as $value) {
                        $playerName[$value['player']] = $value['role'];
                    }

                    // Check if the owner is still in this guild, if not, we choose the highest ranking possible
                    // in this database entry, however, this does not check if the corresponding player has legend rank.
                    if (isset($ownerResult['player']) && isset($playerName[$ownerResult['player']])) {
                        MySQLCredentials::executeInsert("guild.add_member", [
                            'guild_id' => $guildId,
                            'role' => Guild::RANK_LEADER,
                            'player_name' => $guildLeader
                        ], yield, yield Await::REJECT);

                        $this->getLogger()->warning("Recovery method for guild with id='" . $guildId . "' is 0x3");
                        yield Await::ONCE;
                    } else {
                        if (($target = array_search(Guild::RANK_OFFICER, $playerName)) === false) {
                            $target = array_rand($playerName);
                        }

                        MySQLCredentials::executeInsert("guild.add_member", [
                            'guild_id' => $guildId,
                            'role' => Guild::RANK_LEADER,
                            'player_name' => $target,
                        ], yield, yield Await::REJECT);
                        MySQLCredentials::executeInsert("guild.set_leader", [
                            'player_name' => $target,
                            'guild_id' => $guildId,
                        ], yield, yield Await::REJECT);

                        $this->getLogger()->warning("Recovery method for guild with id='" . $guildId . "' is 0x4");

                        yield Await::ALL;
                    }
                }
            }
        }, catches: function (Throwable $error) {
            $this->getLogger()->logException($error);
        });

        if (MySQLCredentials::isDatabaseOnline()) {
            MySQLCredentials::getMySQLDatabase()->waitAll();
        }
    }

    public function getServerData(): ServerData
    {
        return $this->serverData;
    }

    public function getPrefix(): string
    {
        return '§o§l§eN§6G§r§7: ';
    }

    public function getCombatLogger(): CombatLogger
    {
        return $this->combatLogger;
    }

    public function onDisable(): void
    {
        $pluginManager = $this->getServer()->getPluginManager();

        $gamemodes = ["NGSkyBlock", "NGFactions"];
        foreach ($gamemodes as $gamemode) {
            $plugin = $pluginManager->getPlugin($gamemode);
            if ($plugin instanceof PluginBase && !$plugin->isDisabled()) {
                $pluginManager->disablePlugin($plugin);
            }
        }

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->getPlayerData()->saveData($player);
            $this->getPlayerManager()->forceTransfer($player);
        }

        ElasticSearch::getInstance()->close();
        MySQLCredentials::close();

        $this->getServer()->getLogger()->removeAttachment($this->getErrorLogger());
        $this->getLogger()->info('§eNether§6Games §bEssentials disabled!');
    }

    public function getPlayerData(): PlayerData
    {
        return $this->playerData;
    }

    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    public function getPublisher(): ?KafkaPublisher
    {
        return $this->publisher;
    }

    public function getConsumer(): ?KafkaConsumer
    {
        return $this->consumer;
    }

    public function getTournamentManager(): TournamentManager
    {
        return $this->tournamentManager;
    }
}
