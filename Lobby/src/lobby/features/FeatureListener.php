<?php

declare(strict_types=1);

namespace lobby\features;

use lobby\entity\custom\GroundRangeEntity;
use lobby\entity\custom\RangeEntity;
use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\events\NGPlayerTransferEvent;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\PressurePlateUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class FeatureListener implements Listener
{
    use BaseTrait;

    public function __construct(
        private FeaturesManager $featuresManager
    )
    {
    }

    public function onNGLogin(NGLoginEvent $event): void
    {
        $player = $event->getPlayer();

        if (($presents = $this->getFeatureManager()->getPresents()) !== null) {
            $presents->loadPresents($player);

            if (time() < 1761955200) { // check if it's before 1 nov
                $cosmetic = CosmeticHandler::CAPES();

                if (($entry = $cosmetic->getEntry(133)) !== null && !$cosmetic->has($player, $entry)) {
                    $player->sendMessage(TextFormat::GREEN . "You have received a " . $entry->name . " cape!");
                    $cosmetic->give($player, $entry);
                }
            }
        }
    }

    public function getFeatureManager(): FeaturesManager
    {
        return $this->featuresManager;
    }

    public function onPlayerJoin(NGJoinEvent $event): void
    {
        $player = $event->getPlayer();

        if ((($presents = $this->getFeatureManager()->getPresents()) !== null) && !$this->getNGEssentials()->getPlayerData()->getBool($player, PlayerData::FPS_MODE)) {
            $presents->sendWeather($player);
        }

        $this->getFeatureManager()->getParkour()->loadParkourResults($player);
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        $parkour = $this->getFeatureManager()->getParkour();
        if ($parkour->isPlaying($player)) {
            $parkour->stopParkour($player);
        }

        $parkour->unloadParkourResults($player);

        if (($presents = $this->getFeatureManager()->getPresents()) !== null) {
            $presents->savePresents($player, true);
        }
    }

    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        $world = $event->getWorld();
        if ($world->getId() === $world->getServer()->getWorldManager()->getDefaultWorld()->getId()) {
            $this->getFeatureManager()->getPresents()?->setCorrectBiome($event->getChunk());
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $maze = $this->getFeatureManager()->getMaze();

        if ($maze->isPlaying($player)) {
            $maze->addBlockTraveled($player);
        }
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $projectile = $event->getEntity();
        if ($projectile instanceof Arrow) {
            $projectile->flagForDespawn();
        }
    }

    public function onPlayerCommand(CommandEvent $event): void
    {
        $player = $event->getSender();
        if (!$player instanceof Player) {
            return;
        }

        $parkour = $this->getFeatureManager()->getParkour();
        if ($parkour->isPlaying($player)) {
            $player->sendMessage(TextFormat::RED . "You can't run this command while you're attempting the parkour.");
            $event->cancel();
        }
    }

    public function onNGTransfer(NGPlayerTransferEvent $event): void
    {
        if (($presents = $this->getFeatureManager()->getPresents()) !== null) {
            $player = $event->getPlayer();

            if (!$this->getNGEssentials()->getPlayerData()->getBool($player, PlayerData::FPS_MODE)) {
                $presents->sendWeather($event->getPlayer(), true);
            }
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $origin = $event->getOrigin();
        /** @var Player $player */
        $player = $origin->getPlayer();
        $packet = $event->getPacket();

        if ($packet->pid() === InventoryTransactionPacket::NETWORK_ID) {
            /** @var InventoryTransactionPacket $packet */
            if (($packet->trData instanceof UseItemOnEntityTransactionData) && !$event->isCancelled() && !$player->isSpectator()) {
                $entityId = $packet->trData->getActorRuntimeId();
                if (($presentHandler = $this->getFeatureManager()->getPresents()) !== null) {
                    foreach ($presentHandler->getPresents() as $present) {
                        if ($present->getId() === $entityId) {
                            if (($callable = $present->getCallable()) !== null) {
                                $callable($player);
                            }
                            $event->cancel();
                        }
                    }
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @priority LOWEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof Player && $event->getCause() === EntityDamageEvent::CAUSE_LAVA) {
            $parkour = $this->getFeatureManager()->getParkour();
            if ($parkour->isPlaying($player)) {
                $player->teleport($parkour->getCheckPoint($player));
            }
        }
    }

    public function onPlayerPressurePlateTrigger(PressurePlateUpdateEvent $event): void
    {
        $featureManager = $this->getFeatureManager();
        $pos = $event->getBlock()->getPosition();

        foreach ($event->getActivatingEntities() as $player) {
            if (!$player instanceof Player) {
                return;
            }

            switch ([$pos->getX(), $pos->getY(), $pos->getZ()]) {
                case [42, 3, -247]:
                    $featureManager->getMaze()->start($player);
                    break;
                case [42, 3, -266]:
                    $featureManager->getMaze()->stop($player, true, false);
                    break;
                case [-111, 47, 74]:
                    $featureManager->getParkour()->startParkour($player);
                    break;
                case [-127, 60, 111]:
                    $featureManager->getParkour()->setCheckPoint($player, 1);
                    break;
                case [-132, 69, 125]:
                    $featureManager->getParkour()->setCheckPoint($player, 2);
                    break;
                case [-168, 87, 145]:
                    $featureManager->getParkour()->setCheckPoint($player, 3);
                    break;
                case [-223, 95, 145]:
                    $featureManager->getParkour()->setCheckPoint($player, 4);
                    break;
                case [-208, 100, 165]:
                    $featureManager->getParkour()->setCheckPoint($player, 5);
                    break;
                case [-196, 118, 166]:
                    $featureManager->getParkour()->setCheckPoint($player, 6);
                    break;
                case [-199, 140, 166]:
                    $featureManager->getParkour()->stopParkour($player, true);
                    break;
                default:
                    if ($player->getLocation()->distance($player->getWorld()->getSpawnLocation()) < 30) {
                        $yaw = $player->getLocation()->getYaw();

                        if ($yaw > 291 && $yaw < 352) {
                            $motFlat = $player->getDirectionPlane()->normalize()->multiply(10 * 3.75 / 20);//Seems to work almost perfectly
                            $mot = new Vector3($motFlat->x, 0.5, $motFlat->y);

                            $player->setMotion($mot);
                        }
                    }
                    break;
            }
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof RangeEntity && $damager instanceof Player) {
            $range = $entity->getShootingRange();
            $animation = $entity instanceof GroundRangeEntity ? "animation.ng.lobby.target.ground.hit" : "animation.ng.lobby.target.air.pop";

            $damager->getNetworkSession()->sendDataPacket(AnimateEntityPacket::create($animation, "", "", 0, "", 0, [$entity->getId()]));
            $range->addPoint();

            $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($range, $damager, $entity) {
                if (!$entity->isFlaggedForDespawn()) $entity->flagForDespawn();

                $entity = $range->getRandomEntity();
                $entity->spawnTo($damager);

                $range->setRangeEntity($entity);
            }), 17);
        }
    }
}