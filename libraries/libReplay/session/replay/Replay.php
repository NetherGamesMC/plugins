<?php

declare(strict_types=1);


namespace libReplay\session\replay;


use Closure;
use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\elements\Label;
use libforms\FormManager;
use libReplay\protocol\BlockChangePacket;
use libReplay\protocol\PlayerInformationPacket;
use libReplay\session\replay\utils\ReplayIdConverter;
use libReplay\session\replay\utils\ReplayInfo;
use libReplay\session\replay\utils\UuidConverter;
use NetherGames\NGEssentials\commands\PingCommand;
use NetherGames\NGEssentials\NGEssentials;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\VarInt;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\world\WorldException;
use function count;
use function property_exists;
use function spl_object_id;

class Replay
{
    /** @var ByteBufferReader */
    private ByteBufferReader $serializer;
    /** @var TypeConverter */
    private TypeConverter $typeConverter;
    /** @var PacketPool */
    private PacketPool $packetPool;
    /** @var int */
    private int $tick = 0;
    /** @var int|null */
    private ?int $nextTick = null;
    /** @var ReplayIdConverter */
    private ReplayIdConverter $convertor;
    /** @var World */
    private World $world;
    /** @var Selection|null */
    private ?Selection $selection = null;
    /** @var bool */
    private bool $pause = true;
    /** @var int */
    private int $speed = 1;
    /** @var UuidConverter */
    private UuidConverter $uuidConverter;

    /** @var array<int, PlayerInformationPacket> */
    private array $playerInformation = [];
    /** @var array<int, Location> */
    private array $playerLocation = [];

    /**
     * @throws DataDecodeException
     */
    public function __construct(World $world, ReplayInfo $info, string $payload, PacketPool $packetPool)
    {
        $this->world = $world;
        $this->packetPool = $packetPool;
        $this->convertor = new ReplayIdConverter();
        $this->uuidConverter = new UuidConverter();
        $this->serializer = new ByteBufferReader($payload);
        $this->typeConverter = TypeConverter::getInstance($info->getProtocolId());

        if (!$this->checkReachEnd()) {
            $this->nextTick = VarInt::readUnsignedLong($this->serializer);
        }
    }

    public function checkReachEnd(): bool
    {
        if ($this->serializer->getUnreadLength() === 0) {
            $world = $this->getWorld();
            $world->getServer()->broadcastMessage(TextFormat::RED . 'The recording has ended.', $world->getPlayers());

            $this->pause();
            return true;
        }

        return false;
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function pause(): void
    {
        $this->pause = true;
    }

    public function resume(): void
    {
        if (!$this->checkReachEnd()) {
            $this->pause = false;
        }
    }

    public function sendPlayerOptions(Player $player, PlayerInformationPacket $info): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle($info->playerName);

            $form->addButton(new ImageButton('Info', ImageButton::IMAGE_TYPE_FACE, $info->playerName, function (Player $player) use ($info) {
                $this->sendPlayerInformation($player, $info, function (Player $player) use ($info): void {
                    $this->sendPlayerOptions($player, $info);
                });
            }));

            $form->addButton(new Button('Staff Portal', function (Player $player) use ($info) {
                $plugin = NGEssentials::getInstance();
                $enforcementHandler = $plugin->getPlayerManager()->getEnforcementHandler();

                /** @var IPlayer $p */
                $p = $plugin->getServer()->getOfflinePlayer($info->playerName);
                $enforcementHandler->sendPlayerEditor($player, $p);
            }));

            $form->sendForm();
        }
    }

    public function sendPlayerInformation(Player $player, PlayerInformationPacket $info, ?Closure $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Player Info: ' . $info->playerName);

            if ($info->nickName !== '') {
                $form->addElement(new Label('§aNickname: §f' . $info->nickName));
            }

            $form->addElement(new Label('§aPing: §f' . PingCommand::parseColoredPing($info->ping)));
            $form->addElement(new Label('§aOS: §f' . $info->os));
            $form->addElement(new Label('§aModel: §f' . $info->model));
            $form->addElement(new Label('§aControls: §f' . $info->inputName));
            $form->addElement(new Label('§aProxy: §f' . $info->proxy));

            $form->sendForm();
        }
    }

    public function getPlayerSelector(Player $player): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Spectator Menu');

            $form->setContent('Select a player:');
            foreach ($this->playerInformation as $runtimeActorRuntimeId => $playerInformation) {
                $playerName = $playerInformation->playerName;

                $form->addButton(new ImageButton('Teleport to ' . $playerName, ImageButton::IMAGE_TYPE_FACE, $playerName, function (Player $player) use ($runtimeActorRuntimeId) {
                    if (($location = $this->getPlayerLocation($runtimeActorRuntimeId)) === null) {
                        $player->sendMessage(TextFormat::RED . 'This player is not in the recording anymore.');
                    } else {
                        $player->teleport($location->asVector3(), $location->yaw, $location->pitch);
                    }
                }));
            }
            $form->addButton(new ImageButton(TextFormat::RED . TextFormat::BOLD . 'Exit', ImageButton::IMAGE_TYPE_PATH, 'textures/blocks/barrier'));
            $form->sendForm();
        }
    }

    public function getPlayerLocation(int $actorRuntimeId): ?Location
    {
        return $this->playerLocation[$actorRuntimeId] ?? null;
    }

    public function stop(): void
    {
        $packets = [];
        foreach ($this->getConvertor()->getMapping() as $entityId) {
            $packets[] = RemoveActorPacket::create($entityId);
        }

        if (count($packets) > 0) {
            $world = $this->getWorld();
            NetworkBroadcastUtils::broadcastPackets($world->getPlayers(), $packets);
        }
    }

    public function getConvertor(): ReplayIdConverter
    {
        return $this->convertor;
    }

    public function getTick(): int
    {
        return $this->tick;
    }

    /**
     * @throws DataDecodeException
     */
    public function tick(): void
    {
        if ($this->pause) {
            return;
        }

        for ($i = 0; $i < $this->speed; $i++) {
            while ($this->nextTick <= $this->tick) {
                $stream = new ByteBufferReader(CommonTypes::getString($this->serializer));
                /** @var ClientboundPacket[] $packets */
                $packets = [];

                foreach (PacketBatch::decodePackets($stream, $this->typeConverter->getProtocolId(), $this->packetPool) as $packet) {
                    /** @var ClientboundPacket $packet */
                    if ($this->processPacket($packet)) {
                        $packets[] = $packet;
                    }
                }

                $world = $this->getWorld();
                if ($this->selection !== null) {
                    if (count($blocks = $this->selection->getBlocks()) > 10) {
                        AsyncBlockManager::executeSet($this->selection, $world);
                    } else {
                        $blockFactory = RuntimeBlockStateRegistry::getInstance();

                        foreach ($blocks as $hash => $stateId) {
                            $block = $blockFactory->fromStateId($stateId);
                            World::getBlockXYZ($hash, $x, $y, $z);

                            try {
                                $world->setBlockAt($x, $y, $z, $block, false);
                            } catch (WorldException) { /** @phpstan-ignore-line */
                            }
                        }
                    }

                    $this->selection = null;
                }

                if (count($packets) > 0) {
                    TypeConverter::broadcastByTypeConverter($world->getPlayers(), function (TypeConverter $typeConverter) use ($packets): array {
                        return array_map(fn(ClientboundPacket $packet) => $this->convertPacket($typeConverter, $packet), $packets);
                    });
                }

                if ($this->checkReachEnd()) {
                    return;
                }

                $this->nextTick = VarInt::readUnsignedLong($this->serializer);
            }

            $this->tick++;
        }
    }

    private function convertPacket(TypeConverter $typeConverter, ClientboundPacket $packet): ClientboundPacket
    {
        if (spl_object_id($this->typeConverter) === spl_object_id($typeConverter)) {
            return $packet;
        }

        switch ($packet->pid()) {
            case MobEquipmentPacket::NETWORK_ID:
                /** @var MobEquipmentPacket $packet */
                $pk = clone $packet;
                $pk->item = $this->convertItemStackWrapper($typeConverter, $packet->item);
                return $pk;
            case MobArmorEquipmentPacket::NETWORK_ID:
                /** @var MobArmorEquipmentPacket $packet */
                $pk = clone $packet;
                $pk->head = $this->convertItemStackWrapper($typeConverter, $packet->head);
                $pk->chest = $this->convertItemStackWrapper($typeConverter, $packet->chest);
                $pk->legs = $this->convertItemStackWrapper($typeConverter, $packet->legs);
                $pk->feet = $this->convertItemStackWrapper($typeConverter, $packet->feet);
                $pk->body = $this->convertItemStackWrapper($typeConverter, $packet->body);
                return $pk;
            case AddItemActorPacket::NETWORK_ID:
                /** @var AddItemActorPacket $packet */
                $pk = clone $packet;
                $pk->item = $this->convertItemStackWrapper($typeConverter, $packet->item);
                return $pk;
            case AddPlayerPacket::NETWORK_ID:
                /** @var AddPlayerPacket $packet */
                $pk = clone $packet;
                $pk->item = $this->convertItemStackWrapper($typeConverter, $packet->item);
                return $pk;
            case AddActorPacket::NETWORK_ID:
                /** @var AddActorPacket $packet */
                $pk = clone $packet;
                $pk->metadata = $this->convertMetadata($typeConverter, $packet->type, $packet->metadata);
                return $pk;
            case LevelEventPacket::NETWORK_ID:
                /** @var LevelEventPacket $packet */
                $pk = clone $packet;
                switch ($packet->eventId) {
                    case LevelEvent::PARTICLE_PUNCH_BLOCK:
                        $runtimeId = $packet->eventData & 0xFFFFFF;
                        $face = $packet->eventData >> 24;
                        $pk->eventData = $this->convertBlockNetworkRuntimeId($typeConverter, $runtimeId) | ($face << 24);
                        break;
                    case LevelEvent::PARTICLE_DESTROY:
                        $pk->eventData = $this->convertBlockNetworkRuntimeId($typeConverter, $pk->eventData);
                        break;
                    default:
                        if (($packet->eventId & LevelEvent::ADD_PARTICLE_MASK) !== 0) {
                            $particleId = $packet->eventId & LevelEvent::ADD_PARTICLE_MASK;

                            if ($particleId == ParticleIds::TERRAIN) {
                                $pk->eventData = $this->convertBlockNetworkRuntimeId($typeConverter, $pk->eventData);
                            } else if ($particleId === ParticleIds::ITEM_BREAK) {
                                [$id, $meta] = $this->convertItemNetworkId(
                                    $typeConverter,
                                    ($pk->eventData >> 16) & 0xFFFF,
                                    $pk->eventData & 0xFFFF,
                                    0
                                );

                                $pk->eventData = ($id << 16) | $meta;
                            }

                            if ($typeConverter->getProtocolId() <= ProtocolInfo::PROTOCOL_1_20_50 && $particleId >= ParticleIds::BREEZE_WIND_EXPLOSION) {
                                --$particleId;
                                $pk->eventId = $particleId | LevelEvent::ADD_PARTICLE_MASK;
                            }
                        }
                        break;
                }
                return $pk;
            case LevelSoundEventPacket::NETWORK_ID:
                /** @var LevelSoundEventPacket $packet */
                $pk = clone $packet;
                switch ($packet->sound) {
                    case LevelSoundEvent::BREAK:
                    case LevelSoundEvent::PLACE:
                    case LevelSoundEvent::HIT:
                    case LevelSoundEvent::LAND:
                    case LevelSoundEvent::ITEM_USE_ON:
                    case LevelSoundEvent::PRESSURE_PLATE_CLICK_ON:
                    case LevelSoundEvent::PRESSURE_PLATE_CLICK_OFF:
                        $pk->extraData = $this->convertBlockNetworkRuntimeId($typeConverter, $pk->extraData);
                        break;
                    case LevelSoundEvent::NOTE:
                        $instrumentId = ($pk->extraData >> 8) & 0xFF;
                        $note = $pk->extraData & 0xFF;

                        if ($typeConverter->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_50) {
                            if ($instrumentId === 5 || $instrumentId === 7) {
                                $instrumentId++;
                            } elseif ($instrumentId === 6 || $instrumentId === 8) {
                                $instrumentId--;
                            }
                        }

                        $pk->extraData = ($instrumentId << 8) | $note;
                        break;
                }
                return $pk;
            default:
                return $packet;
        }
    }

    private function processPacket(ClientboundPacket $packet): bool
    {
        switch ($pid = $packet->pid()) {
            case BlockChangePacket::NETWORK_ID:
                /** @var BlockChangePacket $packet */
                if ($this->selection === null) {
                    $this->selection = $packet->selection;
                } else {
                    $this->selection->addSelection($packet->selection);
                }
                return false;
            case PlayerInformationPacket::NETWORK_ID:
                /** @var PlayerInformationPacket $packet */
                if (($runtimeActorRuntimeId = $this->getConvertor()->getRuntimeId($packet->actorRuntimeId)) !== null) {
                    $packet->actorRuntimeId = $runtimeActorRuntimeId;

                    $this->playerInformation[$runtimeActorRuntimeId] = $packet;
                }
                return false;
            case MoveActorAbsolutePacket::NETWORK_ID:
                /** @var MoveActorAbsolutePacket $packet */
                if (($runtimeActorRuntimeId = $this->getConvertor()->getRuntimeId($packet->actorRuntimeId)) !== null) {
                    $packet->actorRuntimeId = $runtimeActorRuntimeId;

                    $this->playerLocation[$runtimeActorRuntimeId] = Location::fromObject(
                        $packet->position,
                        null,
                        $packet->yaw,
                        $packet->pitch
                    );
                }
                break;
            case PlayerListPacket::NETWORK_ID:
                /** @var PlayerListPacket $packet FOR PLAYER ENTITIES */
                $uuidConvertor = $this->getUuidConverter();
                if ($packet->type === PlayerListPacket::TYPE_ADD) {
                    $convertor = $this->getConvertor();
                    foreach ($packet->entries as $entry) {
                        if (($convertor->getRuntimeId($internalActorUniqueId = $entry->actorUniqueId)) === null) {
                            $runtimeActorUniqueId = $convertor->addEntity($internalActorUniqueId);
                            $runtimeUuid = $uuidConvertor->addEntity($entry->uuid);

                            $entry->username = (string)$runtimeActorUniqueId;
                            $entry->actorUniqueId = $runtimeActorUniqueId;
                            $entry->uuid = $runtimeUuid;
                        } else {
                            return false;
                        }
                    }
                } else {
                    foreach ($packet->entries as $entry) {
                        /** @var UuidInterface $runtimeUuid */
                        $runtimeUuid = $uuidConvertor->getRuntimeUuid($internalUuid = $entry->uuid);

                        $entry->uuid = $runtimeUuid;

                        $uuidConvertor->removeEntity($internalUuid);
                    }
                }
                break;
            case RemoveActorPacket::NETWORK_ID:
                /** @var RemoveActorPacket $packet */
                $internalActorUniqueId = $packet->actorUniqueId;

                if (($runtimeActorUniqueId = $this->getConvertor()->getRuntimeId($internalActorUniqueId)) === null) {
                    return false;
                }

                $packet->actorUniqueId = $runtimeActorUniqueId;

                unset(
                    $this->playerInformation[$runtimeActorUniqueId],
                    $this->playerLocation[$runtimeActorUniqueId]
                );
                $this->getConvertor()->removeEntity($internalActorUniqueId);
                break;
            case TakeItemActorPacket::NETWORK_ID:
                /** @var TakeItemActorPacket $packet */
                if (($runtimeTakerActorRuntimeId = $this->getConvertor()->getRuntimeId($packet->takerActorRuntimeId)) === null || ($runtimeItemActorRuntimeId = $this->getConvertor()->getRuntimeId($packet->itemActorRuntimeId)) === null) {
                    return false;
                }

                $packet->takerActorRuntimeId = $runtimeTakerActorRuntimeId;
                $packet->itemActorRuntimeId = $runtimeItemActorRuntimeId;
                break;
            case SetActorLinkPacket::NETWORK_ID:
                /** @var SetActorLinkPacket $packet */
                if (!$this->convertLink($packet->link)) {
                    return false;
                }
                break;
            default:
                if (property_exists($packet, 'actorRuntimeId')) {
                    $internalActorRuntimeId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->actorRuntimeId;
                    })->call($packet);

                    if (($runtimeActorRuntimeId = $this->getConvertor()->getRuntimeId($internalActorRuntimeId)) === null) {
                        if ($pid === AddActorPacket::NETWORK_ID || $pid === AddItemActorPacket::NETWORK_ID) {
                            /** @var AddActorPacket|AddItemActorPacket $packet */
                            $runtimeActorRuntimeId = $this->getConvertor()->addEntity($internalActorRuntimeId);
                        } else {
                            return false;
                        }
                    }

                    if ($pid === AddPlayerPacket::NETWORK_ID) {
                        /** @var AddPlayerPacket $packet */
                        $packet->username = (string)$runtimeActorRuntimeId;
                        /** @var UuidInterface $runtimeUuid */
                        $runtimeUuid = $this->getUuidConverter()->getRuntimeUuid($packet->uuid);
                        $packet->uuid = $runtimeUuid;

                        $this->playerLocation[$runtimeActorRuntimeId] = Location::fromObject(
                            $packet->position,
                            null,
                            $packet->yaw,
                            $packet->pitch
                        );

                        $packet->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(
                            $packet->abilitiesPacket->getData()->getCommandPermission(),
                            $packet->abilitiesPacket->getData()->getPlayerPermission(),
                            $runtimeActorRuntimeId,
                            $packet->abilitiesPacket->getData()->getAbilityLayers(),
                        ));

                        foreach ($packet->links as $link) {
                            if (!$this->convertLink($link)) {
                                return false;
                            }
                        }
                    } elseif ($pid === AddActorPacket::NETWORK_ID) {
                        /** @var AddActorPacket $packet variable is needed because of PHPStan :( */
                        $links = $packet->links;

                        foreach ($links as $link) {
                            if (!$this->convertLink($link)) {
                                return false;
                            }
                        }
                    }

                    (function () use ($runtimeActorRuntimeId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->actorRuntimeId = $runtimeActorRuntimeId;
                    })->call($packet);
                }

                if (property_exists($packet, 'actorUniqueId')) {
                    $internalActorUniqueId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->actorUniqueId;
                    })->call($packet);

                    if (($runtimeActorUniqueId = $this->getConvertor()->getRuntimeId($internalActorUniqueId)) === null) {
                        return false;
                    }

                    (function () use ($runtimeActorUniqueId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->actorUniqueId = $runtimeActorUniqueId;
                    })->call($packet);
                }

                if (property_exists($packet, 'targetActorUniqueId')) {
                    $internalTargetActorUniqueId = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->targetActorUniqueId;
                    })->call($packet);

                    if (($runtimeTargetActorUniqueId = $this->getConvertor()->getRuntimeId($internalTargetActorUniqueId)) === null) {
                        return false;
                    }

                    (function () use ($runtimeTargetActorUniqueId): void {
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->targetActorUniqueId = $runtimeTargetActorUniqueId;
                    })->call($packet);
                }

                if (property_exists($packet, 'tick')) {
                    $tick = (function (): int {
                        /** @noinspection PhpUndefinedFieldInspection */
                        /** @phpstan-ignore-next-line */
                        return $this->tick;
                    })->call($packet);

                    if ($tick !== 0) {
                        $serverTick = $this->getWorld()->getServer()->getTick();

                        (function () use ($serverTick): void {
                            /** @noinspection PhpUndefinedFieldInspection */
                            $this->tick = $serverTick;
                        })->call($packet);
                    }
                }
        }

        return true;
    }

    public function getUuidConverter(): UuidConverter
    {
        return $this->uuidConverter;
    }

    private function convertLink(EntityLink $link): bool
    {
        $convertor = $this->getConvertor();

        if (($fromRuntimeActorUniqueId = $convertor->getRuntimeId($link->fromActorUniqueId)) === null) {
            return false;
        }
        $link->fromActorUniqueId = $fromRuntimeActorUniqueId;

        if (($toRuntimeActorUniqueId = $convertor->getRuntimeId($link->toActorUniqueId)) === null) {
            return false;
        }

        $link->toActorUniqueId = $toRuntimeActorUniqueId;
        return true;
    }

    /**
     * @return int[]
     * @phpstan-return array{int, int, ?int}
     *
     * @throws ItemTypeSerializeException
     */
    private function convertItemNetworkId(TypeConverter $typeConverter, int $networkId, int $networkMeta, int $networkBlockRuntimeId): array
    {
        return $typeConverter->getItemTranslator()->toNetworkId($this->typeConverter->getItemTranslator()->fromNetworkId($networkId, $networkMeta, $networkBlockRuntimeId));
    }

    private function convertItemStackWrapper(TypeConverter $typeConverter, ItemStackWrapper $itemStackWrapper): ItemStackWrapper
    {
        return ItemStackWrapper::legacy($this->convertItemStack($typeConverter, $itemStackWrapper->getItemStack()));
    }

    private function convertItemStack(TypeConverter $typeConverter, ItemStack $itemStack): ItemStack
    {
        return $typeConverter->coreItemStackToNet($this->typeConverter->netItemStackToCore($itemStack));
    }

    private function convertBlockNetworkRuntimeId(TypeConverter $typeConverter, int $networkRuntimeId): int
    {
        $recordBlockDictionary = $this->typeConverter->getBlockTranslator()->getBlockStateDictionary();
        $blockDictionary = $typeConverter->getBlockTranslator()->getBlockStateDictionary();

        $blockStateData = $recordBlockDictionary->generateCurrentDataFromStateId($networkRuntimeId);

        return $blockDictionary->lookupStateIdFromData(
            $blockStateData ?? throw new AssumptionFailedError("Failed to convert block state data")
        ) ?? throw new AssumptionFailedError("Failed to convert block network runtime ID");
    }

    /**
     * @param array<int, MetadataProperty> $metadata
     * @return array<int, MetadataProperty>
     */
    private function convertMetadata(TypeConverter $typeConverter, string $type, array $metadata): array
    {
        if (isset($metadata[EntityMetadataProperties::MINECART_DISPLAY_BLOCK])) {
            $property = $metadata[EntityMetadataProperties::MINECART_DISPLAY_BLOCK];
            if (!$property instanceof IntMetadataProperty) {
                throw new AssumptionFailedError("Minecart display block property should be an int");
            }

            $metadata[EntityMetadataProperties::MINECART_DISPLAY_BLOCK] = new IntMetadataProperty(
                $this->convertBlockNetworkRuntimeId($typeConverter, $property->getValue())
            );
        }

        if ($type === EntityIds::FALLING_BLOCK && isset($metadata[EntityMetadataProperties::VARIANT])) {
            $property = $metadata[EntityMetadataProperties::VARIANT];
            if (!$property instanceof IntMetadataProperty) {
                throw new AssumptionFailedError("Variant property should be an int");
            }

            $metadata[EntityMetadataProperties::VARIANT] = new IntMetadataProperty(
                $this->convertBlockNetworkRuntimeId($typeConverter, $property->getValue())
            );
        }

        return $metadata;
    }

    public function getPlayerInformation(int $actorRuntimeId): ?PlayerInformationPacket
    {
        return $this->playerInformation[$actorRuntimeId] ?? null;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function setSpeed(int $speed = 1): void
    {
        $this->speed = $speed;
    }
}