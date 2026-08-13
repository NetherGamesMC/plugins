<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;

class TridentRiptideSound implements Sound
{
    public function __construct(private int $level)
    {
    }

    public function encode(Vector3 $pos): array
    {
        $soundID = match (min(max($this->level, 1), 3)) {
            1 => LevelSoundEvent::ITEM_TRIDENT_RIPTIDE_1,
            2 => LevelSoundEvent::ITEM_TRIDENT_RIPTIDE_2,
            3 => LevelSoundEvent::ITEM_TRIDENT_RIPTIDE_3,
        };
        return [LevelSoundEventPacket::nonActorSound($soundID, $pos, false)];
    }
}