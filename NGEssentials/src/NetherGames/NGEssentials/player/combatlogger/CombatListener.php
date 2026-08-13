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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\combatlogger;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use function time;

class CombatListener implements Listener
{

    public function __construct(private CombatLogger $logger)
    {
    }

    /**
     * @param EntityRegainHealthEvent $event
     *
     * @priority MONITOR
     */
    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player && $entity->getHealth() + $event->getAmount() >= $entity->getMaxHealth()) {
            $this->logger->wipeLog($entity);
        }
    }


    /**
     * @param InventoryOpenEvent $event
     *
     * @priority LOW
     */
    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        if (!($event->getInventory() instanceof BlockInventory)) {
            return;
        }

        $player = $event->getPlayer();
        $plugin = NGEssentials::getInstance();
        $gameSettings = $plugin->getPlayerData()->getGameSettings();

        if ($gameSettings->getBool($player, GameSettings::CHEST_COMBAT_COOLDOWN)) {
            $combatLogger = $plugin->getCombatLogger();
            $combatLog = $combatLogger->getLog($player);
            $cooldown = $gameSettings->getInt($player, GameSettings::COOLDOWN_SECONDS);

            if ($cooldown > 0 && ($hit = $combatLog->getLatestHit()) !== null && $hit->getTime() + $cooldown > time()) {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority MONITOR
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $damaged = $event->getEntity();

            if ($damaged instanceof Player) {
                if ($damager instanceof Player) {
                    if ($damager->getId() !== $damaged->getId()) {
                        $log = $this->logger->getLog($damaged);
                        $log->addHit(new CombatHit($damager, $event->getFinalDamage()));
                    }
                } else {
                    $owner = $damager->getOwningEntity();

                    if ($owner instanceof Player && $damaged->getId() !== $owner->getId()) {
                        $log = $this->logger->getLog($damaged);
                        $log->addHit(new CombatHit($owner, $event->getFinalDamage()));
                    }
                }
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        $this->getLogger()->wipeLog($player, true);
    }

    public function getLogger(): CombatLogger
    {
        return $this->logger;
    }
}