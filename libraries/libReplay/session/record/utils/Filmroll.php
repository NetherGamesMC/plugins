<?php

declare(strict_types=1);


namespace libReplay\session\record\utils;


use libasyncio\blocks\Selection;
use libReplay\protocol\BlockChangePacket;
use libReplay\protocol\PlayerInformationPacket;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\NetworkSessionManager;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\World;
use function property_exists;

class Filmroll extends NetworkSession
{
    /** @var Camera */
    private Camera $camera;
    /** @var Selection|null */
    private ?Selection $selection = null;

    public function __construct(
        Server                    $server,
        NetworkSessionManager     $manager,
        PacketPool                $packetPool,
        PacketSender              $sender,
        PacketBroadcaster         $broadcaster,
        EntityEventBroadcaster    $entityEventBroadcaster,
        Compressor                $compressor,
        TypeConverter             $typeConverter,
        private RecordIdConverter $converter,
        PlayerInfo                $playerInfo
    )
    {
        parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $entityEventBroadcaster, $compressor, $typeConverter, 'localhost', 19132);

        $this->loggedIn = true;
        $this->enableCompression = true;
        $this->info = $playerInfo;
        $manager->markLoginReceived($this);
    }

    public function onBlocksChange(Selection $selection): void
    {
        if ($this->selection === null) {
            $this->selection = $selection;
        } else {
            $this->selection->addSelection($selection);
        }
    }

    public function tick(): void
    {
        if ($this->selection !== null) {
            $this->sendDataPacket(BlockChangePacket::create($this->selection));
            $this->selection = null;
        }

        parent::tick();
    }

    public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false): bool
    {
        switch ($packet->pid()) {
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet FOR PLAYER ENTITIES */
                $pos = $packet->blockPosition;
                $dictionary = $this->getTypeConverter()->getBlockTranslator()->getBlockStateDictionary();
                $blockStateData = $dictionary->generateCurrentDataFromStateId($packet->blockRuntimeId);

                if ($blockStateData === null) {
                    return false;
                }

                $stateId = GlobalBlockStateHandlers::getDeserializer()->deserialize($blockStateData);
                $this->onBlockChangeRaw(World::blockHash($pos->getX(), $pos->getY(), $pos->getZ()), $stateId);
                return true;
            case PlayerListPacket::NETWORK_ID:
                /** @var PlayerListPacket $packet FOR PLAYER ENTITIES */
                if ($packet->type === PlayerListPacket::TYPE_ADD) {
                    $convertor = $this->getConverter();
                    foreach ($packet->entries as $entry) {
                        if (($internalActorUniqueId = $convertor->getInternalId($runtimeId = $entry->actorUniqueId)) === null) {
                            $internalActorUniqueId = $convertor->addEntity($runtimeId);
                        }

                        $entry->actorUniqueId = $internalActorUniqueId;
                        $entry->xboxUserId = '';
                    }
                }
                break;
            case TakeItemActorPacket::NETWORK_ID:
                /** @var TakeItemActorPacket $packet */
                $convertor = $this->getConverter();
                if (($internalTakerActorRuntimeId = $convertor->getInternalId($packet->takerActorRuntimeId)) === null || ($internalItemActorRuntimeId = $convertor->getInternalId($packet->itemActorRuntimeId)) === null) {
                    return false;
                }

                $packet->takerActorRuntimeId = $internalTakerActorRuntimeId;
                $packet->itemActorRuntimeId = $internalItemActorRuntimeId;
                break;
            case RemoveActorPacket::NETWORK_ID:
                /** @var RemoveActorPacket $packet */
                $convertor = $this->getConverter();
                if (($internalActorUniqueId = $convertor->getInternalId($uniqueId = $packet->actorUniqueId)) === null) {
                    return false;
                }

                $packet->actorUniqueId = $internalActorUniqueId;

                $convertor->removeEntity($uniqueId);
                break;
            case AddPlayerPacket::NETWORK_ID:
                /** @var AddPlayerPacket $packet FOR PLAYER ENTITIES */
                $player = $this->getCamera()->getWorld()->getEntity($runtimeActorRuntimeId = $packet->actorRuntimeId);
                $convertor = $this->getConverter();

                if ($player instanceof Player) {
                    $internalActorRuntimeId = $convertor->addEntity($runtimeActorRuntimeId);

                    parent::sendDataPacket(PlayerListPacket::add([PlayerListEntry::createAdditionEntry($uniqueId = $player->getUniqueId(), $internalActorRuntimeId, $player->getDisplayName(), $this->getTypeConverter()->getSkinAdapter()->toSkinData($player->getSkin()), $player->getXuid())]));

                    if ($player instanceof NGPlayer) {
                        $playerData = NGEssentials::getInstance()->getPlayerData();

                        parent::sendDataPacket(PlayerInformationPacket::create(
                            $internalActorRuntimeId,
                            $player->getName(),
                            $playerData->getString($player, PlayerData::NICK),
                            $player->getNetworkSession()->getPing() ?? 0,
                            $player->getDeviceOS(),
                            $player->getDeviceModel(),
                            $player->getInputName(),
                            $player->getProxyRegion()
                        ));
                    }

                    foreach ($packet->links as $index => $link) {
                        if (!$this->convertLink($link)) {
                            unset($packet->links[$index]);
                        }
                    }

                    $packet->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(
                        $packet->abilitiesPacket->getData()->getCommandPermission(),
                        $packet->abilitiesPacket->getData()->getPlayerPermission(),
                        $internalActorRuntimeId,
                        $packet->abilitiesPacket->getData()->getAbilityLayers(),
                    ));

                    $packet->actorRuntimeId = $internalActorRuntimeId;
                    parent::sendDataPacket($packet, $immediate);

                    parent::sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($uniqueId)]));
                } elseif (($internalActorRuntimeId = $convertor->getInternalId($packet->actorRuntimeId)) !== null) {
                    foreach ($packet->links as $index => $link) {
                        if (!$this->convertLink($link)) {
                            unset($packet->links[$index]);
                        }
                    }

                    $packet->actorRuntimeId = $internalActorRuntimeId;
                    return parent::sendDataPacket($packet, $immediate);
                }
                return false;
            case AddActorPacket::NETWORK_ID:
                /** @var AddActorPacket $packet */
                $internalActorRuntimeId = $this->getConverter()->addEntity($packet->actorRuntimeId);

                foreach ($packet->links as $index => $link) {
                    if (!$this->convertLink($link)) {
                        unset($packet->links[$index]);
                    }
                }

                $packet->actorRuntimeId = $internalActorRuntimeId;
                $packet->actorUniqueId = $internalActorRuntimeId;
                break;
            case AddItemActorPacket::NETWORK_ID:
                /** @var AddItemActorPacket $packet */
                $internalActorRuntimeId = $this->getConverter()->addEntity($packet->actorRuntimeId);

                $packet->actorRuntimeId = $internalActorRuntimeId;
                $packet->actorUniqueId = $internalActorRuntimeId;
                break;
            case SetActorLinkPacket::NETWORK_ID:
                /** @var SetActorLinkPacket $packet */
                if (!$this->convertLink($packet->link)) {
                    return false;
                }
                break;
            default:
                if (property_exists($packet, 'actorRuntimeId')) {
                    $actorRuntimeId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->actorRuntimeId;
                    })->call($packet);

                    if (($internalActorRuntimeId = $this->getConverter()->getInternalId($actorRuntimeId)) === null) {
                        return false;
                    }

                    (function () use ($internalActorRuntimeId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->actorRuntimeId = $internalActorRuntimeId;
                    })->call($packet);
                }

                if (property_exists($packet, 'actorUniqueId')) {
                    $actorUniqueId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->actorUniqueId;
                    })->call($packet);

                    if (($internalActorUniqueId = $this->getConverter()->getInternalId($actorUniqueId)) === null) {
                        return false;
                    }

                    (function () use ($internalActorUniqueId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->actorUniqueId = $internalActorUniqueId;
                    })->call($packet);
                }

                if (property_exists($packet, 'targetActorUniqueId')) {
                    $targetActorUniqueId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->targetActorUniqueId;
                    })->call($packet);

                    if (($internalTargetActorUniqueId = $this->getConverter()->getInternalId($targetActorUniqueId)) === null) {
                        return false;
                    }

                    (function () use ($internalTargetActorUniqueId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->targetActorUniqueId = $internalTargetActorUniqueId;
                    })->call($packet);
                }

                break;
        }

        return parent::sendDataPacket($packet, $immediate);
    }

    public function onBlockChangeRaw(int $blockHash, int $stateId): void
    {
        if ($this->selection === null) {
            $this->selection = new Selection();
        }

        $this->selection->addRaw($blockHash, $stateId);
    }

    public function getConverter(): RecordIdConverter
    {
        return $this->converter;
    }

    public function getCamera(): Camera
    {
        return $this->camera;
    }

    public function setCamera(Camera $camera): void
    {
        $this->camera = $camera;
    }

    private function convertLink(EntityLink $link): bool
    {
        $convertor = $this->getConverter();

        if (($fromInternalId = $convertor->getInternalId($link->fromActorUniqueId)) === null) {
            return false;
        }
        $link->fromActorUniqueId = $fromInternalId;

        if (($toInternalId = $convertor->getInternalId($link->toActorUniqueId)) === null) {
            return false;
        }

        $link->toActorUniqueId = $toInternalId;
        return true;
    }

    public function getSender(): CameraPacketSender
    {
        /** @var CameraPacketSender $sender */
        $sender = $this->sender;

        return $sender;
    }
}