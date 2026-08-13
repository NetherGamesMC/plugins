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

use libminigames\Arena;
use libminigames\ArenaListener;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class MSArenaListener extends ArenaListener
{
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onBlockBreak($event);
        } else {
            $event->cancel();
        }
    }

    /**
     * @return MSArena
     */
    public function getArena(): Arena
    {
        /** @var MSArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onBlockBurn($event);
        } else {
            $event->cancel();
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $arena = $this->getArena();
        $damaged = $event->getEntity();

        if (($damaged instanceof Player) && $event->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $event->cancel();
        }

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onEntityDamage($event);
        } else {
            $event->cancel();
        }
    }

    public function onArenaQuit(Player $player): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onArenaQuit($player);
        }
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onBlockPlace($event);
        } else {
            $event->cancel();
        }
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onCraftItem($event);
        } else {
            $event->cancel();
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $arena = $this->getArena();

        $event->cancel();

        $arena->getCurrentGame()?->onEntityDamageByEntity($event);
    }

    public function onEntityShootBow(EntityShootBowEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onEntityShootBow($event);
        }
    }

    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onInventoryClose($event);
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onInventoryOpen($event);
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onInventoryTransaction($event);
        }
    }

    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerChangeSkin($event);
        }
    }

    public function onPlayerChat(NGChatEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerChat($event);
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerDropItem($event);
        }
        $event->cancel();
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerInteract($event);
        }
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerItemConsume($event);
        }
    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerItemHeld($event);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onPlayerQuit($event);
        }
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $arena = $this->getArena();

        if (($game = $arena->getCurrentGame()) !== null) {
            $game->onProjectileHitBlock($event);
        }
    }
}