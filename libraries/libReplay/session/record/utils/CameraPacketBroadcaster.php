<?php

declare(strict_types=1);


namespace libReplay\session\record\utils;


use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;

class CameraPacketBroadcaster implements PacketBroadcaster
{
    /**
     * @param Filmroll[] $recipients
     * @param ClientboundPacket[] $packets
     */
    public function broadcastPackets(array $recipients, array $packets): void
    {
        foreach ($recipients as $filmroll) {
            foreach ($packets as $packet) {
                $filmroll->sendDataPacket(clone $packet);
            }
        }
    }
}