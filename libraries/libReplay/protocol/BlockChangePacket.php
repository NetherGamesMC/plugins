<?php

declare(strict_types=1);

namespace libReplay\protocol;

use libasyncio\blocks\Selection;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use function count;

class BlockChangePacket extends DataPacket implements ClientboundPacket
{
    public const NETWORK_ID = 0xa8;

    /** @var Selection */
    public Selection $selection;

    public static function create(Selection $selection): self
    {
        $result = new self;
        $result->selection = $selection;
        return $result;
    }

    public function handle(PacketHandlerInterface $handler): bool
    {
        return true;
    }

    protected function decodePayload(ByteBufferReader $in, int $protocolId): void
    {
        $selection = new Selection();

        $count = VarInt::readUnsignedInt($in);
        for ($i = 0; $i < $count; ++$i) {
            $selection->addRaw(VarInt::readUnsignedLong($in), VarInt::readUnsignedLong($in));
        }

        $this->selection = $selection;
    }

    protected function encodePayload(ByteBufferWriter $out, int $protocolId): void
    {
        $blocks = $this->selection->getBlocks();

        VarInt::writeUnsignedInt($out, count($blocks));
        foreach ($blocks as $hash => $blockId) {
            VarInt::writeUnsignedLong($out, $hash);
            VarInt::writeUnsignedLong($out, $blockId);
        }
    }
}