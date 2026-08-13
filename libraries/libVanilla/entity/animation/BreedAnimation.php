<?php

declare(strict_types=1);

namespace libVanilla\entity\animation;

use pocketmine\entity\animation\Animation;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class BreedAnimation implements Animation
{

    public function __construct(private Entity $entity)
    {
    }

    public function encode(): array
    {
        return [
            ActorEventPacket::create($this->entity->getId(), ActorEvent::TAME_SUCCESS, 0, null)
        ];
    }
}