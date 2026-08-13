<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\games;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class GamePunch extends Game
{
    public function getMessage(): string
    {
        return 'Punch someone';
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $attacker = $event->getDamager();
        $entity = $event->getEntity();

        if (($attacker instanceof Player && $entity instanceof Player) && !$this->isWinner($attacker->getName())) {
            $this->addWinner($attacker);
        }
    }
}