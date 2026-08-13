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

namespace mommasays;

use libminigames\Minigame;
use libminigames\MinigameListener;
use mommasays\games\Game;
use mommasays\games\GameJumpToVoid;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;

class MSListener extends MinigameListener
{
    public function onPlayerJump(PlayerJumpEvent $event): void
    {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getArena($player);

        if ($arena !== null && $arena->getCurrentGame() !== null) {
            $arena->getCurrentGame()->onPlayerJump($event);
        }
    }

    /**
     * @return MommaSays
     */
    public function getPlugin(): Minigame
    {
        /** @var MommaSays $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $arena = $this->getPlugin()->getArena($player);

        if ($arena !== null && $arena->getCurrentGame() !== null) {
            $arena->getCurrentGame()->onMoveEvent($event);

            if (!$event->isCancelled() && $event->getTo()->getY() <= GameJumpToVoid::VOID_TRESHOLD) {
                // Teleport the player above the border if he for some reason falls below it
                $player->teleport(new Vector3(Game::ARENA_SPAWN_POINT[0], Game::ARENA_SPAWN_POINT[1], Game::ARENA_SPAWN_POINT[2]));
            }
        }
    }
}