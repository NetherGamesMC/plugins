<?php

namespace libVanilla\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;

class CrossbowLoadingStartSound implements Sound
{

    public function encode(Vector3 $pos): array
    {
        return [LevelSoundEventPacket::nonActorSound(LevelSoundEvent::CROSSBOW_LOADING_START, $pos, false)];
    }
}