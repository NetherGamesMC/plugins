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

namespace NetherGames\NGEssentials\entity;

use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGPlayerTransferEvent;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class EntityListener implements Listener
{

    public function __construct(private EntityManager $entityManager)
    {
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority MONITOR
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $player = $event->getEntity();
        $from = $event->getFrom()->getWorld();
        $to = $event->getTo()->getWorld();

        if ($from !== $to && ($player instanceof Player)) {
            $entityManager = $this->getEntityManager();

            $entityManager->despawnEntities([$player], $entityManager->getEntities($from));
            $entityManager->spawnEntities([$player], $entityManager->getEntities($to));
        }
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @param NGJoinEvent $event
     *
     * @priority LOW
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        $player = $event->getPlayer();

        $entityManager = $this->getEntityManager();
        $ess = $entityManager->getPlugin();

        $ess->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entityManager, $player, $ess): void {
            if (!$player->isConnected()) {
                return;
            }

            if ($ess->getServerManager()->getServerType() === ServerManager::LOBBY || $ess->getPlayerData()->getBool($player, PlayerData::BOSS_BAR)) {
                $entityManager->getBossBar()->showTo($player);
            } else {
                $entityManager->getBossBar()->hideFrom($player);
            }
        }), 20);
        $entityManager->spawnEntities([$player], $entityManager->getEntities($player->getWorld()));
    }

    /**
     * @param NGPlayerTransferEvent $event
     *
     * @priority NORMAL
     */
    public function onNGPlayerTransfer(NGPlayerTransferEvent $event): void
    {
        $player = $event->getPlayer();

        $this->getEntityManager()->getBossBar()->hideFrom($player);
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        $this->getEntityManager()->getBossBar()->hideFrom($player);
    }

    /**
     * @param DataPacketSendEvent $event
     * @return void
     */
    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        foreach ($event->getPackets() as $packet) {
            if ($packet instanceof AvailableActorIdentifiersPacket) {
                $packet->identifiers = $this->getEntityManager()->registerCustomEntity($packet);
            }
        }
    }
}