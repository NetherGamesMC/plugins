<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars;

use bedwars\utils\entity\ItemEntity;
use libminigames\Minigame;
use libminigames\MinigameListener;
use libVanilla\item\Fireball;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\tile\Bed;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\utils\TextFormat;

final class BWListener extends MinigameListener
{
    /**
     * @param NGJoinEvent $event
     *
     * @priority NORMAL
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            $player = $event->getPlayer();
            $plugin = $this->getPlugin();
            $ess = $plugin->getEssentials();

            if ($ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                $ess->getPlayerData()->setValue($player, PlayerData::RECONNECT, false);

                if (($arena = $plugin->getArenaByXuid($player->getXuid())) !== null && $arena->isRunning()) {
                    /** @var BWArena $arena */
                    if (($team = $arena->getTeamByXuid($player->getXuid())) !== null) {
                        /** @var BWTeam $team */
                        $team->reconnectPlayer($player);
                        return;
                    }
                }

                $player->sendMessage(TextFormat::RED . "Couldn't connect you to that match, so you were put in another Bedwars match!");

                if ($plugin->isStandAloneGame()) {
                    $plugin->joinArena($player);
                }
            }
        }
    }

    /**
     * @return Bedwars
     */
    public function getPlugin(): Minigame
    {
        /** @var Bedwars $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority NORMAL
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        if (!NGEssentials::isInDevelopmentMode()) {
            $player = $event->getPlayer();
            $ess = $this->getPlugin()->getEssentials();

            if (!$ess->getPlayerData()->getBool($player, PlayerData::RECONNECT)) {
                parent::onNGLogin($event);
            }
        }
    }


    /**
     * @param PlayerBedEnterEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerBedEnter(PlayerBedEnterEvent $event): void
    {
        if ((($arena = $this->getPlugin()->getArenaByWorld($event->getPlayer()->getWorld())) !== null) && $arena->isRunning()) {
            $event->cancel();
        }
    }

    /**
     * @param ChunkLoadEvent $event
     *
     * @priority NORMAL
     */
    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        $world = $event->getWorld();

        if (($arena = $this->getPlugin()->getArenaByWorld($world)) !== null) {
            if ($arena->isWaiting()) {
                return;
            }

            $chunk = $event->getChunk();

            foreach ($chunk->getTiles() as $tile) {
                if ($tile instanceof Bed) {
                    $block = $tile->getBlock();

                    if ($block instanceof \pocketmine\block\Bed) {
                        $team = $arena->getTeamByBed($block);
                        if ($team === null || $block->getOtherHalf() === null) {
                            continue;
                        }

                        if (!$team->isBedAlive()) {
                            foreach ($block->getAffectedBlocks() as $block) {
                                $world->setBlock($block->getPosition(), VanillaBlocks::AIR());
                            }
                        } elseif ($arena->hasBedDefense() && !$team->hasBedDefense()) {
                            $team->setBedDefense(true);

                            $callable = function (Block $block, array $blocks) use ($world, $arena): array {
                                /** @var Block[] $blocks */
                                $changedBlocks = [];

                                foreach ($blocks as $b) {
                                    foreach (Facing::ALL as $side) {
                                        $sideBlock = $b->getSide($side);
                                        if ($sideBlock->getTypeId() !== BlockTypeIds::AIR) {
                                            continue;
                                        }

                                        $changedBlocks[] = $sideBlock;

                                        $world->setBlock($sideBlock->getPosition(), $block);
                                        $arena->getBlockCollector()->addBlock($sideBlock->getPosition());
                                    }
                                }

                                return $changedBlocks;
                            };

                            $callable(VanillaBlocks::END_STONE(), $callable(VanillaBlocks::OAK_PLANKS(), $block->getAffectedBlocks()));
                        }
                    }
                }
            }
        }
    }

    /**
     * @param ItemDespawnEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityDespawn(ItemDespawnEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof ItemEntity && $this->getPlugin()->getArenaByWorld($entity->getWorld()) !== null) {
            $entity->flagForDespawn(); // make sure it's removed before spawning the new item, otherwise it will be merged

            $newEntity = new ItemEntity($entity->getLocation(), $entity->getItem());
            $newEntity->setPickupDelay($entity->getPickupDelay());
            $newEntity->setCanDuplicate($entity->canDuplicate());
            $newEntity->spawnToAll();
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority HIGH
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        parent::onInventoryTransaction($event);
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        parent::onPlayerQuit($event);
        $player = $event->getPlayer();

        /** @var BWArena|null $arena */
        $arena = $this->getPlugin()->getArena($player);
        $arena?->getShop()->onQuit($player);
    }

    /**
     * @param DataPacketReceiveEvent $event
     *
     * @priority NORMAL
     */
    public function onDataReceive(DataPacketReceiveEvent $event): void
    {
        /** @var NGPlayer|null $player */
        $player = $event->getOrigin()->getPlayer();
        if ($player !== null) {
            $packet = $event->getPacket();

            /** @var LevelSoundEventPacket $packet */
            if ($packet->pid() == LevelSoundEventPacket::NETWORK_ID && $packet->sound === LevelSoundEvent::ATTACK_NODAMAGE) {
                if (!($player->getInventory()->getItemInHand() instanceof Fireball && $player->getInputMode() === InputMode::TOUCHSCREEN) || $player->isUsingItem()) {
                    return;
                }

                $player->useHeldItem();
            }
        }
    }
}