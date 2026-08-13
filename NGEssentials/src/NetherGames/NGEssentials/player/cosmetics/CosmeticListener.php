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

namespace NetherGames\NGEssentials\player\cosmetics;

use libVanilla\entity\object\CrossbowArrow as VanillaCrossbowArrow;
use NetherGames\NGEssentials\entity\Arrow;
use NetherGames\NGEssentials\entity\Egg;
use NetherGames\NGEssentials\entity\Snowball;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class CosmeticListener implements Listener
{

    public function __construct(private readonly CosmeticHandler $cosmeticHandler)
    {
    }

    /**
     * @param ProjectileLaunchEvent $event
     *
     * @priority LOW
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();

        $player = $entity->getOwningEntity();
        if (!$player instanceof Player) {
            return;
        }

        if ($this->cosmeticHandler->getPlugin()->getPlayerData()->getBool($player, PlayerData::NICK)) {
            return;
        }

        $newEntity = null;
        $location = $entity->getLocation();
        $modify = false;

        switch ($entity::getNetworkTypeId()) {
            case EntityIds::ARROW:
                /** @var PMArrow $entity */
                if ($entity instanceof VanillaCrossbowArrow) {
                    return;
                }

                if ($entity instanceof Arrow) {
                    $modify = true;
                } else {
                    $newEntity = new Arrow($location, $player, $entity->isCritical(), $entity->saveNBT());
                }
                break;
            case EntityIds::SNOWBALL:
                /** @var PMSnowball $entity */
                if ($entity instanceof Snowball) {
                    $modify = true;
                } else {
                    $newEntity = new Snowball($location, $player, $entity->saveNBT());
                }
                break;
            case EntityIds::EGG:
                /** @var PMEgg $entity */
                if ($entity instanceof Egg) {
                    $modify = true;
                } else {
                    $newEntity = new Egg($location, $player, $entity->saveNBT());
                }
                break;
        }

        if ($newEntity !== null) {
            $newEntity->setMotion($newEntity->getMotion()->multiply(1.1));
            $newEntity->spawnToAll();

            $entity->close();
        } elseif ($modify) {
            $entity->setMotion($entity->getMotion()->multiply(1.1));
        }
    }

    public function getCosmeticHandler(): CosmeticHandler
    {
        return $this->cosmeticHandler;
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority LOW
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $cosmeticHandler = $this->getCosmeticHandler();
        $plugin = $cosmeticHandler->getPlugin();

        if (!$plugin->getPlayerData()->getBool($player, PlayerData::NICK)) {
            CosmeticHandler::CAPES()->equip($player);
            CosmeticHandler::ATTACHABLES()->equip($player);

            if ($plugin->getServerManager()->enableLobbyHandling()) {
                $cosmeticHandler->equipArmorCosmetics($player);
            }
        }
    }

    /**
     * @param PlayerChangeSkinEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $plugin = $this->getCosmeticHandler()->getPlugin();

        if (!$plugin->getPlayerData()->getBool($player, PlayerData::NICK)) {
            CosmeticHandler::CAPES()->onSkinChange($event);
            CosmeticHandler::ATTACHABLES()->onSkinChange($event);
        }
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority MONITOR
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $player = $event->getEntity();
        $cosmeticHandler = $this->getCosmeticHandler();
        $plugin = $cosmeticHandler->getPlugin();
        $serverManager = $plugin->getServerManager();

        if (($player instanceof Player) && $serverManager->enableLobbyHandling() && $serverManager->getServerType() !== ServerManager::LOBBY && ($to = $event->getTo()->getWorld()) !== $event->getFrom()->getWorld()) {
            if ($to === $plugin->getServer()->getWorldManager()->getDefaultWorld()) {
                $this->cosmeticHandler->equipArmorCosmetics($player);
            } else {
                $this->cosmeticHandler->removeArmorCosmetics($player);
            }
        }
    }
}