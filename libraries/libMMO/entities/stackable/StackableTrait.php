<?php
/**
 *   _ _ _     __  __ __  __  ____
 *  | (_) |   |  \/  |  \/  |/ __ \
 *  | |_| |__ | \  / | \  / | |  | |
 *  | | | '_ \| |\/| | |\/| | |  | |
 *  | | | |_) | |  | | |  | | |__| |
 *  |_|_|_.__/|_|  |_|_|  |_|\____/
 *
 * Copyright (C) 2016-2024 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy, TobiasDev, TwistedAsylumMC, Drew, larryTheCoder, Studgi
 */
declare(strict_types=1);

namespace libMMO\entities\stackable;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\utils\TextFormat;
use Throwable;

trait StackableTrait
{
    /** @var int */
    private int $currentStack = 1;

    public function onUpdate(int $currentTick): bool
    {
        if ($this->justCreated || $this->ticksLived % 50 === 0) {
            StackingEngine::alphaPruningStack($this->getStackedEntity());
        }

        return parent::onUpdate($currentTick);
    }

    public abstract function getStackedEntity(): StackableInterface|Entity;

    public function stack(int $stack, int $mode = StackableInterface::MODE_ADDITION): void
    {
        if ($mode === StackableInterface::MODE_ADDITION) {
            $this->currentStack += $stack;
        } else if ($mode === StackableInterface::MODE_SET_VALUE) {
            $this->currentStack = $stack;
        }

        $this->setNameTag($this->getCustomName() . ' ' . TextFormat::GOLD . TextFormat::BOLD . 'x' . $this->currentStack);
        $this->scheduleUpdate();
    }

    public abstract function getCustomName(): string;

    public function initEntity(CompoundTag $nbt): void
    {
        try {
            parent::initEntity($nbt);
        } catch (Throwable) {
            $this->flagForDespawn();
            return;
        }

        $this->currentStack = $nbt->getInt('CurrentStack', 1);

        $customName = $this->getCustomName();
        if ($this->currentStack > 1) {
            $customName .= ' ' . TextFormat::GOLD . TextFormat::BOLD . 'x' . $this->currentStack;
        }
        $this->setNameTag($customName);

        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
    }

    public function kill(): void
    {
        $this->currentStack--;

        parent::kill();

        if ($this->currentStack > 0) {
            $this->setHealth($this->getMaxHealth());

            $this->setNameTag($this->getCustomName() . ' ' . TextFormat::GOLD . TextFormat::BOLD . 'x' . $this->currentStack);
        }
    }

    public function startDeathAnimation(): void
    {
        if ($this->currentStack <= 0) {
            parent::startDeathAnimation();
        } else {
            $viewers = [];
            $cached = [];
            foreach ($this->getViewers() as $viewer) {
                $viewers[$viewer->getNetworkSession()->getProtocolId()][] = $viewer;
                $networkId = $viewer->getNetworkSession()->getProtocolId();
                if (!isset($cached[$viewer->getNetworkSession()->getProtocolId()])) {
                    $metadata = $this->getAllNetworkData();
                    unset($metadata[EntityMetadataProperties::ALWAYS_SHOW_NAMETAG], $metadata[EntityMetadataProperties::NAMETAG], $metadata[EntityMetadataProperties::SCORE_TAG]);

                    $entityId = self::nextRuntimeId();
                    $pk1 = AddActorPacket::create($entityId, $entityId, static::getNetworkTypeId(), $this->location->asVector3(), new Vector3(0, 0, 0), $this->getLocation()->getPitch(), $this->getLocation()->getYaw(), $this->getLocation()->getYaw(), $this->getLocation()->getYaw(), [], $metadata, new PropertySyncData([], []), [], null);

                    $cached[$networkId] = [$entityId, $pk1];
                }
            }

            foreach ($cached as $protocolId => $packet) {
                $pk2 = ActorEventPacket::create($packet[0], ActorEvent::DEATH_ANIMATION, 0, null);

                NetworkBroadcastUtils::broadcastPackets($viewers[$protocolId], [$packet[1], $pk2]);
            }
        }
    }

    public function getStackedAmount(): int
    {
        return $this->currentStack;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setInt('CurrentStack', $this->currentStack);

        return $nbt;
    }
}