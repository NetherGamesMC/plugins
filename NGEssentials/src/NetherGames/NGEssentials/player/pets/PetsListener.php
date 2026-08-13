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
 * @author k3ithos, matcracker, driesboy, CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\player\pets;


use NetherGames\NGEssentials\entity\pets\IPetEntity;
use NetherGames\NGEssentials\entity\pets\IRideableEntity;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function in_array;

class PetsListener implements Listener
{

    public function __construct(private PetsManager $manager)
    {
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority MONITOR
     * @handleCancelled
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $toWorld = $event->getTo()->getWorld();
        $fromWorld = $event->getFrom()->getWorld();
        $player = $event->getEntity();

        if (!$player instanceof Player || ($pet = $this->getManager()->getPetFrom($player)) === null) {
            return;
        }

        if ($pet instanceof IRideableEntity) {
            $pet->removeRider($player);
        }

        if (
            $fromWorld === $toWorld ||
            in_array($this->getManager()->getPlugin()->getServerManager()->getServerType(), [ServerManager::CREATIVE, ServerManager::LOBBY], true) ||
            $this->getManager()->getPlugin()->getPlayerData()->getBool($player, PlayerData::TRACK)
        ) {
            return;
        }

        $defaultLevel = $toWorld->getServer()->getWorldManager()->getDefaultWorld();

        if ($defaultLevel === $fromWorld) {
            $pet->flagForDespawn();
        } elseif ($defaultLevel === $toWorld) {
            $pet->flagForDespawn();
            $this->getManager()->spawnPet($player);
        }
    }

    /**
     * @return PetsManager
     */
    public function getManager(): PetsManager
    {
        return $this->manager;
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority LOW
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$this->getManager()->getPlugin()->getPlayerData()->getBool($player, PlayerData::TRACK)) {
            $this->getManager()->spawnPet($player);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $this->getManager()->removePet($event->getPlayer());
    }

    /**
     * Used for getting values of arrows clicked during the riding of a pet, and when dismounted, as well as throwing the rider off the vehicle when leaving.
     *
     * @param DataPacketReceiveEvent $event
     * @priority LOWEST
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();

        if ($packet->pid() !== InteractPacket::NETWORK_ID && $packet->pid() !== PlayerAuthInputPacket::NETWORK_ID) {
            return;
        }

        if ($player === null || !$player->isConnected() || $player->hasNoClientPredictions()) {
            return;
        }

        if (($pet = $this->getManager()->getPetFrom($player)) === null || $pet->isClosed()) {
            return;
        }

        if (!$pet instanceof IPetEntity || !$pet->isRiddenBy($player)) {
            return;
        }

        if ($packet instanceof PlayerAuthInputPacket) {
            if (!($packet->getMoveVecX() !== 0.0 || $packet->getMoveVecZ() !== 0.0)) return;
            $pet->onRiderControl($player, $packet->getMoveVecZ(), $packet->getMoveVecX());
        } else if ($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
            $pet->removeRider($player);
        }
    }

    public function onPlayerEntityInteract(PlayerEntityInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $entity = $event->getEntity();

        if (($pet = $this->getManager()->getPetFrom($player)) === null || $pet->isClosed() || $player->isSneaking()) {
            return;
        }

        if (!$pet instanceof IPetEntity || $pet->hasRider() || $player !== $pet->getOwningEntity() || $entity !== $pet) {
            return;
        }

        if ($player->hasPermission(Permissions::RANK_TITAN)) {
            $pet->addRider($player);
            $player->sendTip(TextFormat::GRAY . 'Crouch or jump to dismount...');
            return;
        }
        $player->sendMessage(Translator::getTranslationPlayer($player, "pets.ride.noperm"));
    }
}