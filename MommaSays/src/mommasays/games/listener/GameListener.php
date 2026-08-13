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

namespace mommasays\games\listener;

use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
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
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class GameListener
{
    public function onArenaQuit(Player $player): void
    {

    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {

    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {

    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {

    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {

    }

    public function onCraftItem(CraftItemEvent $event): void
    {

    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {

    }

    public function onPlayerChat(NGChatEvent $event): void
    {

    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {

    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {

    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {

    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {

    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {

    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {

    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {

    }

    public function onEntityShootBow(EntityShootBowEvent $event): void
    {

    }

    public function onInventoryClose(InventoryCloseEvent $event): void
    {

    }

    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {

    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {

    }

    public function onPlayerMove(Player $player): void
    {

    }

    public function onPlayerJump(PlayerJumpEvent $event): void
    {

    }

    public function onMoveEvent(PlayerMoveEvent $event): void
    {

    }
}