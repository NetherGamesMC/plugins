<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy, CortexPE
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\entity\pets\walking;

use libVanilla\entity\EntityBase;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;

class MazePet extends EntityBase implements IPetEntity
{
    private const SOUND_TIMER = 20 * 5;

    use WalkingPetTrait;

    public static function getNetworkTypeId(): string
    {
        return "ng:pet_maze";
    }

    public function petEntityBaseTick(int $tickDiff): bool
    {
        if ($this->ticksLived <= 0 || $this->ticksLived % self::SOUND_TIMER !== 0) {
            return true;
        }
        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [AnimateEntityPacket::create('animation.maze_pet.walk', 'animation.maze_pet.setup', '', 0, '', 0, [$this->getId()])]);
        return true;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.0, 1.0);
    }
}