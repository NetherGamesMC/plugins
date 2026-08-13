<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
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

namespace murdermystery\gamemodes;

use libminigames\Arena;
use libminigames\ArenaListener;
use murdermystery\gamemodes\classic\MMArenaListenerClassic;
use murdermystery\utils\StatsData;
use murdermystery\utils\Utils;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class MMArenaListener extends ArenaListener
{
    /**
     * This is executed ONLY when game is running.
     *
     * @param Player $player
     */
    public function onArenaQuit(Player $player): void
    {
        if (!$this->getArena()->isSpectator($player)) {
            $statsData = $this->getArena()->getStatsData();
            $statsData->addValue($player, StatsData::MM_DEATHS);
            $statsData->addValue($player, StatsData::MM_MODE_DEATHS);
        }

        if ($this->getArena()->isWaiting() || $this->getArena()->isStarting()) {
            $this->getArena()->getPlugin()->getChanceHandler()->removePlayerChance($this->getArena(), $player);
        }
    }

    /**
     * @return MMArena
     */
    public function getArena(): Arena
    {
        /** @var MMArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        if ($event->getOrigin() instanceof Arrow) {
            $event->cancel();
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $event->cancel();
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $this->getArena()->getMapFeatures()->onEntityDamageByEntity($event);
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $arrow = $event->getEntity();

        if ($arrow instanceof Arrow) {
            $arrow->flagForDespawn();
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        if (!$this->getArena()->getMapFeatures()->onPlayerInteract($event)) {
            $event->cancel();
        }
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        $this->getArena()->getMapFeatures()->onPlayerItemConsume($event);
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $this->getArena()->getMapFeatures()->onBlockPlace($event);
    }

    public function onPlayerChat(NGChatEvent $event): void
    {
        $player = $event->getPlayer();

        if ($this->getArena()->isSpectator($player)) {
            $event->setDisplayName(TextFormat::clean($player->getDisplayName()));
            $event->setRecipients($this->getArena()->getSpectators());
            $event->setPrefix('§7Dead Chat > ');
            $event->setStaffPrefix('§7Dead Chat Relay > ');
            $event->setSplitter(': ');
        } else {
            $event->setDisplayName($player->getDisplayName());
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        if ($event->getInventory() instanceof ChestInventory) {
            $event->cancel();
        }
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {
        $event->getPlayer()->sendMessage("§cYou can't change your skin while you are in a match.");
        $event->cancel();
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $event->cancel();
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        $cause = $event->getCause();

        $void = $cause === EntityDamageEvent::CAUSE_VOID;
        $lava = $cause === EntityDamageEvent::CAUSE_LAVA;
        $suffocation = $cause === EntityDamageEvent::CAUSE_SUFFOCATION;

        if (($entity instanceof Player) && ($void || $lava || $suffocation) && $entity->getGamemode() !== GameMode::SPECTATOR) {
            if ($void) {
                $entity->sendTitle('§cYOU DIED!', '§eYou fell off the map!', 0, 100, 20);
                $entity->sendMessage('§cYOU DIED! §eYou fell off the map!');
            } elseif ($suffocation) {
                $entity->sendTitle('§cYOU DIED!', '§eYou got stuck in a block!', 0, 100, 20);
                $entity->sendMessage('§cYOU DIED! §eYou got stuck in a block!');
            } else {
                $entity->sendTitle('§cYOU DIED!', '§eYou took a swim in lava!', 0, 100, 20);
                $entity->sendMessage('§cYOU DIED! §eYou took a swim in lava!');
            }

            $this->onPlayerDeath($entity, false);
        }

        $event->cancel();
    }

    /**
     * When you override the method in the child classes, remember to call the parent in the end of method
     * @param Player $player
     * @param bool $spawnCorps
     * @see MMArenaListenerClassic::onPlayerDeath()
     */
    public function onPlayerDeath(Player $player, bool $spawnCorps = true): void
    {
        $this->getArena()->broadcastSound('game.player.hurt', 1, 0.8);

        if ($spawnCorps) {
            Utils::spawnPlayerHuman($player);

            $this->getArena()->addSpectator($player);
        } else {
            $this->getArena()->addSpectator($player);

            $player->teleport($this->getArena()->getPlugin()->getArenaConfig()->getRandomSpawn($this->getArena()));
        }

        $player->sendMessage('§eAs a Spectator, you can talk in chat with fellow Spectators.');
        $player->sendMessage("§6Alive players cannot see dead players' chat.");

        $statsData = $this->getArena()->getStatsData();
        $statsData->addValue($player, StatsData::MM_DEATHS);
        $statsData->addValue($player, StatsData::MM_MODE_DEATHS);
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $event->cancel();
    }

    public function onStructureGrow(StructureGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }
}