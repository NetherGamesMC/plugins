<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
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

namespace duels;

use duels\utils\Kits;
use duels\utils\StatsData;
use libminigames\Arena;
use libminigames\ArenaListener;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\TNT;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use function preg_replace;
use function str_replace;

class DuelsArenaListener extends ArenaListener
{
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($this->getArena()->getType() === DuelsArena::TYPE_BUILDUHC) {
            $block = $event->getBlock();
            if (($blockCollector = $this->getArena()->getBlockCollector())->isBreakable($pos = $block->getPosition())) {
                $blockCollector->removeBlock($pos);
                return;
            }
        }
        $event->cancel();
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
       if ($this->getArena()->isSoloGame()) {
           $event->cancel();
       }
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        if (($event->getTo()->getY() - $event->getFrom()->getY()) >= 4) {
            $event->cancel();
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (VanillaItems::MUSHROOM_STEW()->equals($item)) {
            $health = $player->getHealth() + 6;
            if ($health >= $player->getMaxHealth()) {
                $player->setHealth($player->getMaxHealth());
            } else {
                $player->setHealth($health);
            }
            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }
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
        } elseif ($this->getArena()->isSoloGame()) {
            $event->setDisplayName($player->getDisplayName());
        } else {
            $team = $this->getArena()->getTeam($player);
            $event->setDisplayName($team->getPlayerName($player));

            if ($this->getArena()->isRunning()) {
                if (str_starts_with(TextFormat::clean($event->getMessage()), '!')) {
                    $event->setMessage(preg_replace('/!/', '', $event->getMessage(), 1));
                } else {
                    $event->setRecipients($team->getAlivePlayers());
                    $event->setPrefix($team->getColor() . 'Team > ');
                    $event->setStaffPrefix('§fTeam Chat Relay > ');
                }
            }
        }
    }

    /**
     * @return DuelsArena
     */
    public function getArena(): Arena
    {
        /** @var DuelsArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();

        if ($damager instanceof Player) {
            $type = $this->getArena()->getType();
            $entity = $event->getEntity();

            if ($type === DuelsArena::TYPE_SUMO) {
                $event->setBaseDamage(0);
                foreach ($event->getModifiers() as $type => $_) {
                    $event->setModifier(0, $type);
                }
                $event->setKnockBack($event->getKnockBack() * 1.075);
            } elseif ($entity instanceof NGPlayer && $type === DuelsArena::TYPE_COMBO) {
                $event->setAttackCooldown(2);
                $event->setKnockBack($event->getKnockBack() * 0.82);
                $event->setVerticalKnockBackLimit(0.20);
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $team = $this->getArena()->getTeam($player);
            $type = $this->getArena()->getType();

            if ($event->getFinalDamage() >= $event->getEntity()->getHealth()) {
                $event->cancel();
                $ess = $this->getArena()->getPlugin()->getEssentials();

                $cause = $event->getCause();
                switch ($cause) {
                    case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                    case EntityDamageEvent::CAUSE_PROJECTILE:
                        if ($event instanceof EntityDamageByEntityEvent) {
                            $damager = $event->getDamager();
                            if ($damager instanceof Player) {
                                if ($this->getArena()->isSoloGame()) {
                                    $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                                } else {
                                    $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                                }

                                $this->getArena()->addKill($damager, $player);

                                $this->onPlayerDeath($player, $team);
                            }
                        }
                        break;
                    case EntityDamageEvent::CAUSE_FALL:
                    case EntityDamageEvent::CAUSE_VOID:
                    case EntityDamageEvent::CAUSE_LAVA:
                        if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                            if ($this->getArena()->isSoloGame()) {
                                $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause(), true)), true);
                            } else {
                                $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause(), true)), true);
                            }

                            $this->getArena()->addKill($damager, $player);
                        } elseif ($this->getArena()->isSoloGame()) {
                            $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                        } else {
                            $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                        }

                        $this->onPlayerDeath($player, $team, $cause === EntityDamageEvent::CAUSE_FALL);
                        break;
                    default:
                        if ($this->getArena()->isSoloGame()) {
                            $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                        } else {
                            $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                        }
                        $this->onPlayerDeath($player, $team, false);
                        break;
                }
            } elseif ($event->getCause() === EntityDamageEvent::CAUSE_FALL && $this->getArena()->getType() !== DuelsArena::TYPE_BUILDUHC) {
                $event->cancel();
            }
        }
    }

    public function onPlayerDeath(Player $player, DuelsTeam $team, bool $spawnCorps = true): void
    {
        if ($this->getArena()->getGameSettings()->hasRespawns()) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::INSTANT_HEALTH(), amplifier: 10));
            if ($this->getArena()->getGameSettings()->hasRekitOnRespawn()) {
                Kits::giveKit($player, $this->getArena()->getType());
            }
            $spawn = $this->getArena()->getPlugin()->getArenaConfig()->getTeamSpawn($this->getArena(), $team->getId()) ?? $player->getWorld()->getSafeSpawn();
            $player->teleport($spawn);
        } else {
            $statsData = $team->getArena()->getStatsData();
            $statsData->addValue($player, StatsData::DEATHS);
            $statsData->addValue($player, StatsData::DUELS_DEATHS);

            $player->sendTitle('§l§cYOU DIED!', '§7You are now a spectator.', 0, 100, 20);

            if ($spawnCorps) {
                if ($team->getSize() > 1) {
                    foreach ($player->getDrops() as $item) {
                        $player->getWorld()->dropItem($player->getLocation(), $item);
                    }
                }

                $team->getArena()->addSpectator($player);
            } else {
                $team->getArena()->addSpectator($player);

                $player->teleport($player->getWorld()->getSafeSpawn());
            }
        }
    }

    public function onStructureGrow(StructureGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $item = $event->getItem();
        $arena = $this->getArena();

        if ($arena->getType() === DuelsArena::TYPE_BUILDUHC && !$this->getArena()->getGameSettings()->hasNoBuildHeightLimit()) {
            $spawnPos = $arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, 5);
            if ($spawnPos instanceof Location) {
                $maxY = $spawnPos->getY() + 4;

                foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
                    if ($y > $maxY) {
                        $event->cancel();
                        $player->sendConditionalMessage(TextFormat::RED . "Can't place blocks here!");
                        return;
                    }
                }

                foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
                    $arena->getBlockCollector()->addBlock(new Position($x, $y, $z, $player->getWorld()));
                }
                $item->pop();
                return;
            }
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            if ($block instanceof TNT) {
                $block->ignite();
                $item->pop();
                $player->getInventory()->setItemInHand($item);
            }
        }

        $event->cancel();
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }
}