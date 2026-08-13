<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames;

use libminigames\events\MinigameStartEvent;
use libminigames\utils\Forms;
use libminigames\utils\Items;
use libminigames\utils\TypeArena;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;

/**
 * A representation of an arena-specific listener. This listener functions will be called when the
 * conditions for these methods are applicable, for more information see {@see MinigameListener}.
 * In short, the events will be filtered and be passed here.
 *
 * Note for developers: When you are implementing your own event in MinigameListener
 * class, try to make sure that event will be passed in here.
 *
 * @package libminigames
 */
class ArenaListener
{

    public function __construct(protected Arena $arena)
    {
    }

    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
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

    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event): void
    {

    }

    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {

    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {

    }

    /**
     * This event will be called only if the arena is not an instanceof {@link TeamArena}.
     * In order to identify if the player has left the arena is to override the
     * function {@link Team::removePlayer()}.
     *
     * @param Player $player
     */
    public function onArenaQuit(Player $player): void
    {

    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {

    }

    /**
     * @caution This event is cancelled by default.
     */
    public function onBlockMelt(BlockMeltEvent $event): void
    {

    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {

    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {

    }

    public function onEntityExplode(EntityExplodeEvent $event): void
    {

    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {

    }

    /**
     * Entity Combust only gets called for Players
     *
     * @param EntityCombustEvent $event
     */
    public function onEntityCombust(EntityCombustEvent $event): void
    {

    }

    /**
     *
     * This method is a wrapper for PlayerItemHeldEvent & is executed under one of two conditions:
     * 1. When a mobile player holds an item
     * 2. When a PC player interacts with an item
     *
     * The caller of this method only executes it when the arena's status is: either {@link Arena::STATUS_STARTING} or {@link Arena::STATUS_WAITING}
     *
     * TODO: PlayerItemHeldEvent may not be the best way to handle this as changing
     * the item in hand will call this method, causing unnecessary complexity for players.
     *
     * @param Player $player
     * @param Item $item
     * @return bool
     */
    public function onItemInteract(Player $player, Item $item): bool
    {
        $arena = $this->getArena();

        if ($arena instanceof TypeArena && $item->equals(Items::getTypeSelectionAnvil())) {
            Forms::sendTypeSelector($player, $arena);
            return true;
        }
        if ($arena->isPrivateGame() && $item->equals(Items::getGameSettingsBlazeRod())) {
            Forms::sendSettingsMenu($player, $arena);
            return true;
        }
        if ($item->equals(Items::getManualStart())) {
            $player->broadcastSound(new ClickSound(), [$player]);
            $arena->startImmediately();
            return true;
        }

        return false;
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
    }

    /**
     * This event will only be called only if the entity is not an instanceof
     * {@see Player}.
     *
     * @param EntitySpawnEvent $event
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void
    {
    }

    /**
     * This event will only be called when the entity is an instanceof {@see Player} and
     * the arena is currently running.
     *
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
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

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
    }

    /**
     * Only calls when {@see EntityDamageByEntityEvent::getEntity()} is an instanceof
     * {@see Player} and {@see MinigameListener::onEntityDamage()} isn't cancelled
     *
     * @param EntityDamageByEntityEvent $event
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
    }

    public function onBlockForm(BlockFormEvent $event): void
    {
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
    }

    public function onStructureGrow(StructureGrowEvent $event): void
    {
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
    }

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
    }

    public function onMinigameStart(MinigameStartEvent $event): void
    {
    }

    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void
    {
    }

    public function onEntityPreExplode(EntityPreExplodeEvent $event): void
    {
    }

    public function onEntityEffectAdd(EntityEffectAddEvent $event): void
    {
    }

    public function onEntityEffectRemove(EntityEffectRemoveEvent $event): void
    {
    }
}