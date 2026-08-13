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
 * @author k3ithos, matcracker, driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets;

use libPhysX\internal\Rotation;
use libVanilla\entity\ai\state\EntityState;
use libVanilla\entity\Animal;
use NetherGames\NGEssentials\entity\pets\state\FollowOwnerState;
use NetherGames\NGEssentials\player\pets\events\PetRespawnEvent;
use NetherGames\NGEssentials\utils\ParticleOptimizer;
use pocketmine\entity\Entity;
use pocketmine\entity\FoodSource;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use pocketmine\world\particle\HeartParticle;

trait PetEntityTrait
{
    use RideableTrait;

    private Vector3 $followOffset;
    private bool $lerpTeleport = false;

    public function __construct(Location $location, ?Entity $owningEntity, ?CompoundTag $nbt = null)
    {
        $this->setOwningEntity($owningEntity);
        parent::__construct($location, $nbt);
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $parent = $this->baseEntityBaseTick($tickDiff);
        $custom = $this->petEntityBaseTick($tickDiff);
        return $parent || $custom;
    }

    public function petEntityBaseTick(int $tickDiff): bool
    {
        return false;
    }

    public function getDefaultState(): EntityState
    {
        return new FollowOwnerState($this);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($this->closed || !$this->isAlive()) {
            return;
        }

        if ($this->hasRider() && $source->getCause() === $source::CAUSE_FALL) {
            $source->cancel();
        }

        if (($petOwner = $this->getOwningEntity()) !== null) {
            $ownerDamageEvent = new EntityDamageEvent($petOwner, EntityDamageEvent::CAUSE_CUSTOM, 0);
            $ownerDamageEvent->call();
            if ($ownerDamageEvent->isCancelled()) {
                $source->cancel();
            }
            $ownerDamageEvent->cancel();
        }

        parent::attack($source);
    }

    public function getFollowOffset(): Vector3
    {
        return $this->followOffset ??= $this->refreshFollowOffset();
    }

    public function refreshFollowOffset(): Vector3
    {
        $offsetFactor = self::getOffsetDistanceFromPlayer() + ($this->getScale() / 2);
        return $this->followOffset = new Vector3(
            (Utils::getRandomFloat() * 2 - 1) * $offsetFactor,
            (Utils::getRandomFloat() * 2 - 1) * $offsetFactor,
            (Utils::getRandomFloat() * 2 - 1) * $offsetFactor
        );
    }

    protected static function getOffsetDistanceFromPlayer(): float
    {
        return 1.5;
    }

    public function getName(): string
    {
        return 'PetEntity';
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        // this is to allow overriding "onInteract", and just call "attemptToFeedFrom" first everytime...
        // that way, we can have "sneak" behavior for wolves and cats
        return $this->attemptToFeedFrom($player);
    }

    protected function attemptToFeedFrom(Player $player): bool
    {
        $hand = $player->getInventory()->getItemInHand();
        if (!($hand instanceof FoodSource || ($this instanceof Animal && $this->isFoodItem($hand))) || $this->getHealth() >= $this->getMaxHealth()) {
            return false;
        }
        $heal = ($hand instanceof FoodSource ? ((int)($hand->getFoodRestore() / 40 * $this->getMaxHealth())) : ($this->getMaxHealth() * 0.4)) + 2;
        if ($this->getHealth() + $heal > $this->getMaxHealth()) {
            $heal = $this->getMaxHealth() - $this->getHealth();
        }
        $hand->pop();
        $player->getInventory()->setItemInHand($hand);
        $this->heal(new EntityRegainHealthEvent($this, $heal, EntityRegainHealthEvent::CAUSE_SATURATION));
        ParticleOptimizer::getInstance()->addParticle(new HeartParticle(4), $this->getEyePos()->add(0, 1, 0), $this->getWorld());
        return true;
    }

    public function moveTo(Vector3 $pos): void
    {
        $this->getNavigator()->setGoal($this->getSafeLocation($pos)->add(0.5, 0, 0.5));
    }

    public function getSafeLocation(Vector3 $reference): Vector3
    {
        return $this->location->world->getSafeSpawn($reference);
    }

    public function stopMoving(): void
    {
        $this->getNavigator()->setGoal(null);
    }

    public function lerpTeleport(Vector3 $pos): void
    {
        $this->lerpTeleport = true;
        $this->teleport($this->getSafeLocation($pos)); // todo
        $this->lerpTeleport = false;
    }

    public function initEntity(CompoundTag $nbt): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::TAMED, true);

        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();

        parent::initEntity($nbt);

        $this->initPetData($nbt);
    }

    protected function initPetData(CompoundTag $nbt): void
    {
    }

    public function tryLookAtOwner(): void
    {
        $owner = $this->getOwningEntityInWorld();
        if ($owner === null) {
            return;
        }
        $this->lookAt($owner->getEyePos());
    }

    /**
     * Faster than raw Entity->getOwningEntity(), no world-iterations required...
     * And we didn't have to store a reference to the owning player
     */
    final public function getOwningEntityInWorld(): ?Entity
    {
        if ($this->ownerId === null) {
            return null;
        }
        return $this->location->world->getEntity($this->ownerId);
    }

    protected function broadcastMovement(bool $teleport = false): void
    {
        $newRotation = $this->getClientSideRotation();
        if (!$this->lerpTeleport) {
            $originalYaw = $this->location->yaw;
            $originalPitch = $this->location->pitch;

            $this->location->yaw = $newRotation->yaw;
            $this->location->pitch = $newRotation->pitch;

            parent::broadcastMovement($teleport);

            $this->location->yaw = $originalYaw;
            $this->location->pitch = $originalPitch;
        } else {
            NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [MoveActorAbsolutePacket::create(
                $this->id,
                $this->getOffsetPosition($this->location),
                $newRotation->pitch,
                $newRotation->yaw,
                $newRotation->yaw,
                (
                    ($teleport ? MoveActorAbsolutePacket::FLAG_TELEPORT : 0) |
                    ($this->onGround ? MoveActorAbsolutePacket::FLAG_GROUND : 0)
                )
            )]);
        }
    }

    protected function getClientSideRotation(): Rotation
    {
        return new Rotation($this->location->yaw, $this->location->pitch);
    }

    protected function onDeath(): void
    {
        parent::onDeath();

        $ev = new PetRespawnEvent($this, 15);
        $ev->call();
        if ($ev->isCancelled()) {
            $this->flagForDespawn();
            return;
        }

        $this->maxDeadTicks = $ev->getDelay() * 20;
    }
}