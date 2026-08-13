<?php

declare(strict_types=1);

namespace conquests\utils\entity\flag;

use conquests\Conquests;
use conquests\CQArena;
use conquests\CQTeam;
use conquests\shops\Upgrade;
use conquests\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\PopSound;
use function floor;
use function str_repeat;

abstract class BaseFlagEntity extends Entity
{
    public const FLAG_REMOVAL_SECONDS = 30;
    public const FLAG_CAPTURE_TICKS = 3 * 20;
    public const FLAG_CAPTURE_PROGRESS_BAR_LENGTH = 50;

    /** @var Vector3 */
    private Vector3 $spawnPosition;
    /** @var SetActorLinkPacket|null */
    protected ?SetActorLinkPacket $linkPacket = null;
    /** @var int */
    private int $removalTime = self::FLAG_REMOVAL_SECONDS;
    private int $captureTimer = self::FLAG_CAPTURE_TICKS;
    private ?Player $capturingPlayer = null;
    private CQTeam $team;
    private int $dropTimer = 0;

    public function __construct(Location $location, CompoundTag $nbt)
    {
        parent::__construct($location, $nbt);
        $this->spawnPosition = $location->asVector3()->floor()->add(.5, .5, .5);

        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
        $this->setNoClientPredictions(true);
        $this->setSilent(true);
        $this->setHasGravity(true);
    }

    public function canBeCollidedWith(): bool
    {
        return false;
    }

    public function setCapturingPlayer(?Player $capturingPlayer): void
    {
        if ($this->capturingPlayer === null && $capturingPlayer !== null) {
            $this->getOwningTeam()->broadcastMessage($capturingPlayer->getNameTag() . TextFormat::GOLD . ' is attempting to steal your flag!');
        }

        $this->capturingPlayer = $capturingPlayer;

        if ($capturingPlayer === null) {
            $this->captureTimer = self::FLAG_CAPTURE_TICKS;
        } else {
            $playerTeam = $this->getTeamOfPlayer($capturingPlayer);
            $multiplier = $playerTeam?->getFlagPickupSpeedMultiplier() ?? 1.0;
            $this->captureTimer = (int)floor(self::FLAG_CAPTURE_TICKS / $multiplier);
        }
    }

    public function setHasGravity(bool $v = true): void
    {
        parent::setHasGravity($v);
        $this->networkPropertiesDirty = true;
    }

    public function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $dyeColor = DyeColorIdMap::getInstance()->fromId($nbt->getByte("Color"));

        /** @var CQArena|null $arena */
        $arena = Conquests::getInstance()->getArenaByWorld($this->getWorld());
        if ($arena === null) {
            throw new AssumptionFailedError("Arena for flag {$this} not found");
        }

        foreach ($arena->getTeams() as $team) {
            if ($team->getDyeColor() === $dyeColor) {
                $this->team = $team;
                return;
            }
        }

        throw new AssumptionFailedError("Owning team for flag {$this} not found");
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setByte("Color", DyeColorIdMap::getInstance()->toId($this->getOwningTeam()->getDyeColor()));

        return $nbt;
    }

    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->hasCarrier() || $this->capturingPlayer !== null || $player->isSpectator() || $player->isSneaking() || $this->isCarrier($player)) {
            return;
        }

        $owningTeam = $this->getOwningTeam();
        $playerTeam = $this->getTeamOfPlayer($player);

        if ($playerTeam === null || $owningTeam === $playerTeam) {
            return;
        }

        $this->setCapturingPlayer($player);
    }

    private function hasCarrier(): bool
    {
        return $this->getOwningEntityId() !== null;
    }

    protected function getOwningTeam(): CQTeam
    {
        return $this->team;
    }

    protected function getArena(): CQArena
    {
        return $this->getOwningTeam()->getArena();
    }

    private function getTeamOfPlayer(Player $player): ?CQTeam
    {
        return $this->getArena()->getTeamNull($player);
    }

    public function isAtSpawn(): bool
    {
        return $this->getSpawn()->floor()->equals($this->getLocation()->asVector3()->floor());
    }

    public function getSpawn(): Vector3
    {
        return $this->spawnPosition;
    }

    /**
     * @param Player|null $owner
     */
    public function setOwningEntity(?Entity $owner): void
    {
        /** @var Player|null $currentOwner */
        $currentOwner = $this->getOwningEntity();

        // remove effect from previous carrier if needed
        if ($currentOwner instanceof Player) {
            $owningTeam = $this->getOwningTeam();
            if ($owningTeam->getUpgradeLevel(Upgrade::FLAG_WEIGHT()) > 0) {
                $currentOwner->getEffects()->remove(VanillaEffects::SLOWNESS());
            }
        }

        parent::setOwningEntity($owner);

        $this->setHasGravity($owner === null);

        // apply effect to new carrier if needed
        if ($owner instanceof Player) {
            $owningTeam = $this->getOwningTeam();
            if ($owningTeam->getUpgradeLevel(Upgrade::FLAG_WEIGHT()) > 0) {
                $owner->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 999999, 0, false));
            }
            $this->removalTime = self::FLAG_REMOVAL_SECONDS;
            $this->hideNametag();
        }

        $this->getOwningTeam()->updateScoreboard();
    }

    protected function showNametag(): void
    {
        $this->setNameTag(TextFormat::GOLD . 'Flag will be returned in ' . $this->removalTime . TextFormat::GOLD . ' second' . ($this->removalTime === 1 ? '' : 's'));
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
    }

    protected function hideNametag(): void
    {
        $this->setNameTag('');
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
    }

    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $this->teleportToBase();

            $team = $this->getOwningTeam();
            $arena = $team->getArena();
            $arena->broadcastMessage($team->getColor() . $team->getName() . TextFormat::GOLD . "'s flag has been returned", true);
        }
    }

    public function teleportToBase(): void
    {
        $this->teleport($this->getSpawn());

        $this->setOwningEntity(null);

        $this->removalTime = self::FLAG_REMOVAL_SECONDS;
        $this->hideNametag();
    }

    public function setMotion(Vector3 $motion): bool
    {
        return false;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->hasCarrier()) {
            /** @var Player|null $carrier */
            $carrier = $this->getOwningEntity();

            if ($carrier !== null) {
                if ($carrier->isAlive() && $carrier->isConnected()) {
                    if ($this->needsPositionUpdate($carrier)) {
                        $this->setPositionAndRotation($carrier->getLocation()->add(0, 1, 0), $carrier->getLocation()->getYaw(), 0);
                        $hasUpdate = true;
                    }

                    if ($carrier->isSneaking()) {
                        if ($this->dropTimer === 0) {
                            $this->dropTimer = self::FLAG_CAPTURE_TICKS;
                            $carrier->sendMessage("§eYou are dropping your flag.");
                        } else {
                            $this->dropTimer -= $tickDiff;
                            $progress = (int)floor(($this->dropTimer / self::FLAG_CAPTURE_TICKS) * self::FLAG_CAPTURE_PROGRESS_BAR_LENGTH);
                            $carrier->sendPopup(TextFormat::colorize(
                                str_repeat(TextFormat::RED . '|', $progress) . str_repeat(TextFormat::GRAY . '|', self::FLAG_CAPTURE_PROGRESS_BAR_LENGTH - $progress)
                            ));
                            if ($this->dropTimer <= 0) {
                                $carrier->sendMessage("§cYou dropped your flag!");
                                $carrier->broadcastSound(new PopSound());

                                $this->getArena()->dropFlag($carrier);
                                $this->dropTimer = 0;
                            }
                        }
                    } else {
                        if ($this->dropTimer > 0) {
                            $carrier->sendMessage("§cFlag drop cancelled.");
                            $this->dropTimer = 0;
                        } else if ($this->ticksLived % 20 === 0) {
                            $carrier->sendPopup(TextFormat::GOLD . "You're carrying the flag!");
                        }
                    }

                    if (($carrierTeam = $this->getCarrierTeam()) !== null) {
                        if ($this->isInBase($carrierTeam)) {
                            $this->captureFlag($carrier);
                        }

                        return $hasUpdate;
                    }
                }

                $this->setOwningEntity(null);
                $hasUpdate = true;
            }
        } else {
            $this->dropTimer = 0;
        }

        if ($this->capturingPlayer !== null) {
            if (!$this->capturingPlayer->isConnected() || !$this->capturingPlayer->isAlive() || $this->capturingPlayer->isSpectator() || $this->capturingPlayer->getPosition()->distanceSquared($this->getPosition()) > 6) {
                $this->setCapturingPlayer(null);
                return $hasUpdate;
            }

            if ($this->capturingPlayer->isSneaking()) {
                $this->capturingPlayer->sendMessage("§cFlag pickup cancelled because you crouched!");
                $this->setCapturingPlayer(null);
                return $hasUpdate;
            }

            /** @var NGPlayer $player */
            $player = $this->capturingPlayer;

            $this->captureTimer -= $tickDiff;

            if ($this->captureTimer >= 0) {
                $progress = (int)floor(($this->captureTimer / self::FLAG_CAPTURE_TICKS) * self::FLAG_CAPTURE_PROGRESS_BAR_LENGTH);

                $player->playSound('random.pop');
                $player->sendPopup(TextFormat::colorize(str_repeat(TextFormat::GREEN . '|', $progress) . str_repeat(TextFormat::GRAY . '|', self::FLAG_CAPTURE_PROGRESS_BAR_LENGTH - $progress)));

                /** @var NGPlayer $alivePlayer */
                foreach ($this->getOwningTeam()->getAlivePlayers() as $alivePlayer) {
                    $alivePlayer->playSound('random.pop');
                    $alivePlayer->sendPopup(TextFormat::colorize(str_repeat(TextFormat::RED . '|', $progress) . str_repeat(TextFormat::GRAY . '|', self::FLAG_CAPTURE_PROGRESS_BAR_LENGTH - $progress)));
                }
            } else {
                $owningTeam = $this->getOwningTeam();

                $atSpawn = $this->isAtSpawn();
                $this->setOwningEntity($player);

                $player->playSound('random.explode');
                $player->sendPopup('');

                $arena = $owningTeam->getArena();

                /** @var NGPlayer $alivePlayer */
                foreach ($this->getOwningTeam()->getAlivePlayers() as $alivePlayer) {
                    $alivePlayer->playSound('random.explode');
                    $alivePlayer->sendPopup('');
                }

                if ($atSpawn) {
                    $arena->broadcastMessage($player->getNameTag() . TextFormat::GOLD . ' stole ' . $owningTeam->getColor() . $owningTeam->getName() . TextFormat::GOLD . "'s flag.", true);
                    $owningTeam->broadcastTitle(TextFormat::RED . TextFormat::BOLD . 'ALERT', $player->getNameTag() . TextFormat::GOLD . ' stole your flag.');

                    $statsData = $arena->getStatsData();
                    $statsData->addValue($player, StatsData::CQ_FLAGS_COLLECTED);
                } else {
                    $arena->broadcastMessage($player->getNameTag() . TextFormat::GOLD . ' picked up ' . $owningTeam->getColor() . $owningTeam->getName() . TextFormat::GOLD . "'s flag.", true);
                }

                $this->setCapturingPlayer(null);
            }
        } else {
            $this->setRotation($this->getLocation()->getYaw() + 5, 0);
            $hasUpdate = true;

            if ($this->ticksLived % 20 === 0 && !$this->isAtSpawn()) {
                --$this->removalTime;

                if ($this->removalTime === 0) {
                    $this->teleportToBase();

                    $team = $this->getOwningTeam();
                    $arena = $team->getArena();
                    $arena->broadcastMessage($team->getColor() . $team->getName() . TextFormat::GOLD . ' returned their flag.', true);
                } else {
                    $this->setNameTag(TextFormat::GOLD . 'Flag will be returned in ' . $this->removalTime . TextFormat::GOLD . ' second' . ($this->removalTime === 1 ? '' : 's'));
                }
            }
        }

        return $hasUpdate;
    }

    protected function needsPositionUpdate(Entity $player): bool
    {
        return $this->getLocation()->distanceSquared($player->getLocation()->add(0, 1, 0)) > 1;
    }

    private function isInBase(CQTeam $team): bool
    {
        $arena = $this->getArena();

        return $this->getLocation()->distance($arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, $team->getId())) < 7;
    }

    private function isCarrier(Player $player): bool
    {
        foreach ($this->getWorld()->getEntities() as $flag) {
            if ($flag instanceof BaseFlagEntity && ($flag->getOwningEntityId() === $player->getId() || $flag->capturingPlayer === $player)) {
                return true;
            }
        }

        return false;
    }

    private function getCarrierTeam(): ?CQTeam
    {
        if ($this->hasCarrier()) {
            $player = $this->getOwningEntity();
            if ($player instanceof Player) {
                return $this->getTeamOfPlayer($player);
            }
        }
        return null;
    }

    protected function captureFlag(Player $player): void
    {
        /** @var CQTeam $team */
        $team = $this->getTeamOfPlayer($player);

        $statsData = $team->getArena()->getStatsData();
        $statsData->addValue($player, StatsData::CQ_FLAGS_CAPTURED);

        /** @var NGPlayer $player */
        $player->playSound('random.toast');
        $team->increaseScore($player);

        $this->teleportToBase();
    }

    protected function checkBlockIntersections(): void
    {

    }

    protected function sendSpawnPacket(Player $player): void
    {
        parent::sendSpawnPacket($player);

        $networkSession = $player->getNetworkSession();
        if ($this->linkPacket !== null) {
            $networkSession->sendDataPacket($this->linkPacket);
        }
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
        $properties->setGenericFlag(EntityMetadataFlags::HAS_COLLISION, false);
        $properties->setGenericFlag(EntityMetadataFlags::FIRE_IMMUNE, true);
        $properties->setGenericFlag(EntityMetadataFlags::ONFIRE, false);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.99, 0.495);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.02;
    }

    protected function getInitialGravity(): float
    {
        return 0.08;
    }
}