<?php

namespace libVanilla\entity\animation;

use pocketmine\entity\animation\Animation;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class CrossbowChargedAnimation implements Animation
{
    public function __construct(private Living $holder)
    {
    }

    public function encode(): array
    {
        return [
            ActorEventPacket::create($this->holder->getId(), ActorEvent::CHARGED_ITEM, 0, null)
        ];
    }
}
