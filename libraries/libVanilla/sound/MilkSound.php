<?php

declare(strict_types=1);


namespace libVanilla\sound;


use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;

class MilkSound implements Sound
{
    public function encode(Vector3 $pos): array
    {
        return [LevelSoundEventPacket::nonActorSound(LevelSoundEvent::MILK, $pos, false)];
    }
}