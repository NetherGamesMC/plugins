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

namespace NetherGames\NGEssentials\entity;

use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\player\Player;
use pocketmine\utils\Limits;

class BossBar
{
    /** @var int */
    protected int $entityId;
    /** @var Player[] */
    protected array $viewers = [];
    /** @var EntityMetadataCollection */
    protected EntityMetadataCollection $metadata;

    public function __construct(protected string $title = '', protected float $healthPercent = 1.0)
    {
        $this->entityId = Limits::INT32_MAX;

        $metadata = new EntityMetadataCollection();
        $metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
        $metadata->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
        $this->metadata = $metadata;
    }

    public function showTo(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        $pos = $player->getPosition()->asVector3();

        $networkSession->sendDataPacket(AddActorPacket::create(
            $this->getEntityId(),
            $this->getEntityId(),
            EntityIds::SLIME,
            $pos->add(0, -$pos->getFloorY() - 10, 0),
            null,
            0,
            0,
            0,
            0,
            [],
            $this->metadata->getAll(),
            new PropertySyncData([], []),
            []
        ));

        $networkSession->sendDataPacket(UpdateAttributesPacket::create($this->entityId, array_map(static function (Attribute $attr): UpdateAttribute {
            return new UpdateAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), $attr->getMinValue(), $attr->getMaxValue(), []);
        }, [new Attribute(Attribute::HEALTH, $this->healthPercent, 1.0, 1.0)]), 0));
        $networkSession->sendDataPacket(BossEventPacket::show($this->entityId, $this->getTitle(), $this->getHealthPercent()));
        $networkSession->sendDataPacket(BossEventPacket::healthPercent($this->entityId, $this->getHealthPercent()));

        $this->viewers[$player->getId()] = $player;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getHealthPercent(): float
    {
        return $this->healthPercent;
    }

    public function setTitle(string $title, bool $update = true): void
    {
        $this->title = $title;

        if ($update) {
            $this->updateForAll();
        }
    }

    public function updateForAll(): void
    {
        foreach ($this->viewers as $player) {
            $this->updateFor($player);
        }
    }

    public function updateFor(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        $networkSession->sendDataPacket(UpdateAttributesPacket::create($this->entityId, array_map(static function (Attribute $attr): UpdateAttribute {
            return new UpdateAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), $attr->getMinValue(), $attr->getMaxValue(), []);
        }, [new Attribute(Attribute::HEALTH, $this->healthPercent, 1.0, 1.0)]), 0));
        $networkSession->sendDataPacket(BossEventPacket::title($this->entityId, $this->getTitle()));
        $networkSession->sendDataPacket(BossEventPacket::healthPercent($this->entityId, $this->getHealthPercent()));
    }

    public function setHealthPercent(float $hp, bool $update = true): void
    {
        $this->healthPercent = $hp;

        if ($update) {
            $this->updateForAll();
        }
    }

    public function hideFrom(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        $networkSession->sendDataPacket(BossEventPacket::hide($this->entityId));
        $networkSession->sendDataPacket(RemoveActorPacket::create($this->entityId));

        unset($this->viewers[$player->getId()]);
    }

    /**
     * @return Player[]
     */
    public function getViewers(): array
    {
        return $this->viewers;
    }
}
