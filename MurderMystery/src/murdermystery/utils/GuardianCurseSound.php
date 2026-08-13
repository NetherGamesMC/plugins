<?php
declare(strict_types=1);

namespace murdermystery\utils;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\sound\Sound;

class GuardianCurseSound implements Sound
{

    public function encode(?Vector3 $pos): array
    {
        return [LevelEventPacket::create(LevelEvent::GUARDIAN_CURSE, 10, $pos)];
    }
}