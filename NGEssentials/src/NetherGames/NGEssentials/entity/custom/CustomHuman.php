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

use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function array_fill;

class CustomHuman extends Custom
{
    /** @var UuidInterface */
    private UuidInterface $uuid;

    public function __construct(Location $location, string $username, private Skin $skin)
    {
        $this->uuid = Uuid::uuid4();

        parent::__construct($location, $username);

        $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
        $this->getMetadata()->setByte(EntityMetadataProperties::COLOR, 0);
    }

    public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket
    {
        $location = $this->getLocation();

        return AddPlayerPacket::create(
            $this->getUuid(),
            $this->getUsername(),
            $this->getId(),
            "",
            $location->asVector3(),
            null,
            $location->pitch,
            $location->yaw,
            $location->yaw,
            ItemStackWrapper::legacy(ItemStack::null()),
            ProtocolGameMode::SURVIVAL,
            $this->metadata->getAll(),
            new PropertySyncData([], []),
            UpdateAbilitiesPacket::create(new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $this->getId() /* TODO: this should be unique ID */, [
                new AbilitiesLayer(
                    AbilitiesLayer::LAYER_BASE,
                    array_fill(0, AbilitiesLayer::NUMBER_OF_ABILITIES, false),
                    0.0,
                    0.0,
                    0.0
                )
            ])),
            [],
            "",
            DeviceOS::UNKNOWN
        );
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function spawn(Player $player): void
    {
        $networkSession = $player->getNetworkSession();
        $networkSession->sendDataPacket(PlayerListPacket::add([$entry = $this->getListEntry($networkSession->getTypeConverter())]));

        parent::spawn($player);

        $networkSession->sendDataPacket(PlayerListPacket::remove([$entry]));
    }

    public function getListEntry(TypeConverter $typeConverter): PlayerListEntry
    {
        return PlayerListEntry::createAdditionEntry($this->getUuid(), $this->getId(), $this->getUsername(), $typeConverter->getSkinAdapter()->toSkinData($this->getSkin()));
    }

    public function getSkin(): Skin
    {
        return $this->skin;
    }

    public function setSkin(Skin $skin): void
    {
        $this->skin = $skin;
    }

    public function getSkinPacket(TypeConverter $typeConverter): PlayerSkinPacket
    {
        return PlayerSkinPacket::create($this->getUuid(), "", "", $typeConverter->getSkinAdapter()->toSkinData($this->getSkin()));
    }

    public function getMovePacket(): ClientboundPacket
    {
        $location = $this->getLocation();

        return MovePlayerPacket::simple(
            $this->getId(),
            $this->getOffsetPosition($location, false),
            $location->pitch,
            $location->yaw,
            $location->yaw,
            MovePlayerPacket::MODE_NORMAL,
            false,
            0,
            0,
        );
    }

    public function getOffsetPosition(Vector3 $vector3, bool $useScale = true): Vector3
    {
        return $vector3->add(0, 1.62 * ($useScale ? $this->getScale() : 1), 0);
    }

    public function updateNametag(): void
    {
        $username = $this->getUsername();

        parent::updateNametag();

        if (($bool = $username === TextFormat::GRAY . 'Pumpkin') || $username === TextFormat::GREEN . 'Pumpkin - Found') {
            $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, $bool ? 0 : 1);
            $this->metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 1);
        }
    }
}