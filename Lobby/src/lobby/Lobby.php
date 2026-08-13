<?php

declare(strict_types=1);

namespace lobby;

use GlobalLogger;
use lobby\entity\EntityManager;
use lobby\entity\minecraft\registry\ConditionRegistry;
use lobby\features\activity\ActivityManager;
use lobby\features\checkpoint\CheckpointManager;
use lobby\features\FeaturesManager;
use lobby\player\PlayerManager;
use lobby\utils\BaseTrait;
use lobby\utils\Blocks;
use lobby\utils\npc\NpcDialog;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class Lobby extends PluginBase
{
    use BaseTrait, SingletonTrait;

    /** @var PlayerManager */
    private PlayerManager $playerManager;
    /** @var FeaturesManager */
    private FeaturesManager $featuresManager;
    /** @var EntityManager */
    private EntityManager $entityManager;
    /** @var ActivityManager */
    private ActivityManager $activityManager;
    /** @var CheckpointManager */
    private CheckpointManager $checkpointManager;

    public static function getInstance(): Lobby
    {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::setInstance($this);

        NpcDialog::register($this);
        ConditionRegistry::register($this);
        Blocks::register($this->getServer());

        $this->getServer()->getWorldManager()->loadWorld("ArcadeLobby"); // Load arcade lobby

        $this->playerManager = new PlayerManager();
        $this->featuresManager = new FeaturesManager();
        $this->entityManager = new EntityManager();
        $this->activityManager = new ActivityManager();
        $this->checkpointManager = new CheckpointManager();

        $this->getServer()->getPluginManager()->registerEvents(new LobbyListener(), $this);
        $this->getServer()->getLogger()->info(TextFormat::YELLOW . "Nether" . TextFormat::GOLD . "Games" . TextFormat::GREEN . " Lobby enabled!");
    }

    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    public function getFeaturesManager(): FeaturesManager
    {
        return $this->featuresManager;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getCheckpointManager(): CheckpointManager
    {
        return $this->checkpointManager;
    }

    public function getActivityManager(): ActivityManager
    {
        return $this->activityManager;
    }
}
