<?php

declare(strict_types=1);

namespace libReplay\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class PlayerInformationPacket extends DataPacket implements ClientboundPacket
{
    public const NETWORK_ID = 0xa9;

    public int $actorRuntimeId;
    public string $playerName;
    public string $nickName;
    public int $ping;
    public string $os;
    public string $model;
    public string $inputName;
    public string $proxy;

    public static function create(int $actorRuntimeId, string $playerName, string $nickName, int $ping, string $os, string $model, string $inputName, string $proxy): self
    {
        $result = new self;
        $result->actorRuntimeId = $actorRuntimeId;
        $result->playerName = $playerName;
        $result->nickName = $nickName;
        $result->ping = $ping;
        $result->os = $os;
        $result->model = $model;
        $result->inputName = $inputName;
        $result->proxy = $proxy;
        return $result;
    }

    public function handle(PacketHandlerInterface $handler): bool
    {
        return true;
    }

    protected function decodePayload(ByteBufferReader $in, int $protocolId): void
    {
        $this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
        $this->playerName = CommonTypes::getString($in);
        $this->nickName = CommonTypes::getString($in);
        $this->ping = LE::readUnsignedInt($in);
        $this->os = CommonTypes::getString($in);
        $this->model = CommonTypes::getString($in);
        $this->inputName = CommonTypes::getString($in);
        $this->proxy = CommonTypes::getString($in);
    }

    protected function encodePayload(ByteBufferWriter $out, int $protocolId): void
    {
        CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
        CommonTypes::putString($out, $this->playerName);
        CommonTypes::putString($out, $this->nickName);
        LE::writeUnsignedInt($out, $this->ping);
        CommonTypes::putString($out, $this->os);
        CommonTypes::putString($out, $this->model);
        CommonTypes::putString($out, $this->inputName);
        CommonTypes::putString($out, $this->proxy);
    }
}