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

namespace NetherGames\NGEssentials\entity\custom;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;

abstract class Custom
{
    /** @var EntityMetadataCollection */
    protected EntityMetadataCollection $metadata;
    /** @var string */
    private string $username;
    /** @var int */
    private int $runtimeId;
    /** @var float */
    private float $scale = 1.0;

    public function __construct(protected Location $location, string $username)
    {
        $this->runtimeId = Entity::nextRuntimeId();
        $this->metadata = new EntityMetadataCollection();

        $this->setUsername($username);
    }

    public function getMetadata(): EntityMetadataCollection
    {
        return $this->metadata;
    }

    public function spawn(Player $player): void
    {
        $networkSession = $player->getNetworkSession();
        $networkSession->sendDataPacket($this->getSpawnPacket($networkSession->getTypeConverter()));
    }

    abstract public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket;

    public function getMovePacket(): ClientboundPacket
    {
        $location = $this->getLocation();

        return MoveActorAbsolutePacket::create($this->getId(), $location->asVector3(), $location->getPitch(), $location->getYaw(), $location->getYaw(), ((0) | (0)));
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getId(): int
    {
        return $this->runtimeId;
    }

    public function despawn(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket($this->getDespawnPacket());
    }

    public function getDespawnPacket(): RemoveActorPacket
    {
        return RemoveActorPacket::create($this->getId());
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;

        $this->updateNametag();
    }

    public function updateMetadata(): void
    {
        $world = $this->getLocation()->getWorld();

        TypeConverter::broadcastByTypeConverter($world->getPlayers(), fn($typeConverter): array => [$this->getMetadataPacket($typeConverter)]);
    }

    public function getMetadataPacket(TypeConverter $typeConverter): SetActorDataPacket
    {
        return SetActorDataPacket::create($this->getId(), $this->metadata->getAll(), new PropertySyncData([], []), 0);
    }

    public function getScale(): float
    {
        return $this->scale;
    }

    public function setScale(float $scale): void
    {
        $this->scale = $scale;

        $this->metadata->setFloat(EntityMetadataProperties::SCALE, $scale);
    }

    public function updateNametag(): void
    {
        $username = $this->getUsername();

        $this->metadata->setString(EntityMetadataProperties::NAMETAG, $username);
    }
}