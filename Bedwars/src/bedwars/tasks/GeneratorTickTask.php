<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\tasks;

use bedwars\BWArena;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\scheduler\Task;
use function count;

class GeneratorTickTask extends Task
{
    public function __construct(private readonly BWArena $arena)
    {
    }

    public function onRun(): void
    {
        $arena = $this->arena;

        if (!$arena->isRunning()) {
            $this->getHandler()?->cancel();
            return;
        }

        foreach ($arena->getTeamGenerators() as $generator) {
            $generator->tick();
        }

        $packets = [];

        foreach ($arena->getGlobalGenerators() as $generator) {
            $customBlock = $generator->getCustomFakeBlock();
            $location = $customBlock->getLocation();

            $yaw = $location->getYaw() + 25;
            if ((int)$location->getYaw() === 360) {
                $yaw = 0;
            }
            $location->yaw = $yaw;

            $packets[] = $customBlock->getMovePacket();
        }

        if (count($packets) > 0) {
            $players = $arena->getPlugin()->getEssentials()->getPlayerManager()->unsetFPSPlayers($arena->getPlayers());
            NetworkBroadcastUtils::broadcastPackets($players, $packets);
        }
    }
}