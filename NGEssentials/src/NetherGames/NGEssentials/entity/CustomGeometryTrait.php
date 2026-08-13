<?php

namespace NetherGames\NGEssentials\entity;

use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function array_fill;

trait CustomGeometryTrait
{
    private ?UuidInterface $skinUuid = null;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::PLAYER;
    }

    public function getOffsetPosition(Vector3 $vector3): Vector3
    {
        return $vector3->add(0, 1.62, 0);
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $skin = $this->getSkin();
        $location = $this->getLocation();
        $this->skinUuid ??= Uuid::uuid3(Uuid::NIL, ((string)$this->getId()) . $skin->getSkinData() . $this->getName());
        $entityID = $this->getId();
        $name = $this->getName();
        $networkSession = $player->getNetworkSession();

        $networkSession->sendDataPacket(PlayerListPacket::add([
            PlayerListEntry::createAdditionEntry($this->skinUuid, $entityID, $name, $networkSession->getTypeConverter()->getSkinAdapter()->toSkinData($skin))
        ]));

        $networkSession->sendDataPacket(AddPlayerPacket::create(
            $this->skinUuid, $name, $entityID,
            "", $location, $this->motion,
            $location->pitch, $location->yaw, $location->yaw,
            ItemStackWrapper::legacy(ItemStack::null()),
            ProtocolGameMode::SURVIVAL,
            $this->getAllNetworkData(),
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
            array_values($this->links),
            "", DeviceOS::UNKNOWN
        ));

        $networkSession->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($this->skinUuid)]));
    }

    abstract public function getSkin(): Skin;
}