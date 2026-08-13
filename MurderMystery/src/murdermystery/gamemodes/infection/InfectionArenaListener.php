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

namespace murdermystery\gamemodes\infection;

use libminigames\Arena;
use murdermystery\gamemodes\MMArenaListener;
use murdermystery\utils\Items;
use murdermystery\utils\MMKnife;
use murdermystery\utils\StatsData;
use murdermystery\utils\Utils;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Bow;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use function count;
use function min;

class InfectionArenaListener extends MMArenaListener
{
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        /** @var Sword $item */
        $item = $event->getItem();

        if ($item->equals(Items::getKnife($player))) {
            if ($this->getArena()->getAlpha() === $player) {
                if ($item->getDamage() === 0) {
                    $item->setDamage($item->getMaxDurability() - 3);

                    $player->getInventory()->setItemInHand($item);
                } else {
                    return;
                }
            } else {
                $resources = $player->getInventory()->getItem(Items::RESOURCE_SLOT);

                if (!$resources->isNull() && $resources->getCount() >= 10) {
                    $resources->pop(10);
                    $player->getInventory()->setItem(Items::RESOURCE_SLOT, $resources);
                } else {
                    return;
                }
            }
            MMKnife::throwKnife($player);
        }

        parent::onPlayerItemUse($event);
    }

    /**
     * @return MMArenaInfection
     */
    public function getArena(): Arena
    {
        /** @var MMArenaInfection $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        parent::onEntityDamageByEntity($event);

        if (!$event->isCancelled()) {
            /** @var Player $entity */
            $entity = $event->getEntity();
            /** @var Player $damager */
            $damager = $event->getDamager();

            $statsData = $this->getArena()->getStatsData();

            if ($event instanceof EntityDamageByChildEntityEvent) {
                $child = $event->getChild();
                if (($child instanceof Arrow) && $this->getArena()->isInfected($entity) && $entity->getGamemode() !== GameMode::SPECTATOR) {
                    $this->onPlayerDeath($entity);

                    $statsData->addKill($damager, $entity, StatsData::KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_BOW_KILLS);

                    $this->getArena()->addKill($damager);
                    $event->cancel();
                } elseif ($child instanceof MMKnife) {
                    if (!$this->getArena()->isInfected($entity)) {
                        Utils::playSound($damager, 'random.levelup', 1, 1);
                        $this->onPlayerDeath($entity);

                        $statsData->addKill($damager, $entity, StatsData::KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_THROW_KNIFE_KILLS);

                        $this->getArena()->addKill($damager);

                        if ($damager === $this->getArena()->getAlpha()) {
                            $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'The alpha' . TextFormat::GRAY . ' infected ' . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($entity), true);
                        } else {
                            $this->getArena()->broadcastMessage(TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($damager) . ' infected ' . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($entity), true);
                        }
                    }
                    $event->cancel();
                }
            } elseif ($this->getArena()->isInfected($damager) && !$this->getArena()->isInfected($entity) && $damager->getInventory()->getItemInHand()->equals(Items::getKnife($damager), false, false)) {
                Utils::playSound($damager, 'random.levelup', 1, 1);
                $this->onPlayerDeath($entity);

                $statsData->addKill($damager, $entity, StatsData::KILLS);
                $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                $statsData->addKill($damager, $entity, StatsData::MM_KNIFE_KILLS);

                $this->getArena()->addKill($damager);

                if ($damager === $this->getArena()->getAlpha()) {
                    $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'The alpha' . TextFormat::GRAY . ' infected ' . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($entity), true);
                } else {
                    $this->getArena()->broadcastMessage(TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($damager) . ' infected ' . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($entity), true);
                }
                $event->cancel();
            }
        }
    }

    public function onPlayerDeath(Player $player, bool $spawnCorps = true): void
    {
        $scoreboard = $this->getArena()->getScoreboard();

        $this->getArena()->resetPlayer($player);

        if ($this->getArena()->getAlpha() === $player) {
            $this->getArena()->addAlphaDeath();

            if ($this->getArena()->getAlphaDeaths() === 1) {
                $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'The alpha infected, ' . TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player) . TextFormat::YELLOW . ', has been revealed!', true);

                $player->setSkin($this->getArena()->getInfectedSkin());
                $player->sendSkin();
            } else {
                $scoreboard->setLine($this->getArena()->getPlayers(), 6, CustomIcon::ALEX_HEAD . TextFormat::RED . 'Dead');
                foreach ($this->getArena()->getInfected() as $infected) {
                    $infected->sendTitle(TextFormat::RED . 'The Alpha died', TextFormat::YELLOW . 'You will no longer respawn', 0, 60, 20);
                }
                foreach ($this->getArena()->getSurvivors() as $survivor) {
                    $survivor->sendTitle(TextFormat::RED . 'The Alpha died', TextFormat::YELLOW . 'Infected will no longer respawn', 0, 60, 20);
                }
            }
        }

        if (!$this->getArena()->isInfected($player)) {
            $this->getArena()->addInfected($player);

            $scoreboard->setLine([$player], 13, CustomIcon::GAMEMODE . TextFormat::RED . 'Infected');
            $scoreboard->setLines($this->getArena()->getPlayers(), [
                9 => CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($this->getArena()->getSurvivors()),
                8 => CustomIcon::ZOMBIE_HEAD . TextFormat::RED . count($this->getArena()->getInfected()),
            ]);

            $this->getArena()->broadcastSound('game.player.hurt', 1, 0.8);

            $countSurvivors = count($this->getArena()->getSurvivors());
            if ($countSurvivors === 0) {
                return;
            }

            if (!$this->getArena()->getGameSettings()->hasRevealIdentities() && $countSurvivors === 1) {
                $this->getArena()->broadcastMessage(TextFormat::GREEN . 'Survivors' . TextFormat::YELLOW . ' have been revealed!', true);

                foreach ($this->getArena()->getSurvivors() as $survivor) {
                    $survivor->setNameTag($this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getNameTag($survivor, TextFormat::GREEN, true, true));
                }
            }

            $player->setSkin($this->getArena()->getInfectedSkin());
            $player->sendSkin();
        } elseif (!$this->getArena()->canRespawn()) {
            $this->getArena()->removeInfected($player);

            $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 8, CustomIcon::ZOMBIE_HEAD . TextFormat::RED . count($this->getArena()->getInfected()));

            if ($spawnCorps) {
                $player->sendTitle(TextFormat::RED . 'YOU DIED', TextFormat::YELLOW . 'You were shot by a survivor!', 0, 100, 20);
            }

            parent::onPlayerDeath($player, $spawnCorps);
            return;
        }

        $player->setGamemode(GameMode::SPECTATOR);
        $this->getArena()->getPlugin()->getScheduler()->scheduleRepeatingTask(new class($this->getArena(), $player) extends Task {
            /** @var MMArenaInfection */
            private MMArenaInfection $arena;
            /** @var Player */
            private Player $player;
            /** @var int */
            private int $time = 5;

            public function __construct(MMArenaInfection $arena, Player $player)
            {
                $this->arena = $arena;
                $this->player = $player;
            }

            public function onRun(): void
            {
                if (!$this->player->isConnected() || $this->arena->isFinishing() || !$this->arena->isInArena($this->player)) { //HACK
                    $this->getHandler()->cancel();
                } elseif ($this->time > 1) {
                    $this->player->sendTitle(TextFormat::BOLD . TextFormat::RED . 'YOU DIED!', TextFormat::YELLOW . 'Respawning in ' . TextFormat::RED . $this->time . TextFormat::YELLOW . ' seconds!');
                    $this->time--;
                } elseif ($this->time === 1) {
                    $this->player->sendTitle(TextFormat::BOLD . TextFormat::RED . 'YOU DIED!', TextFormat::YELLOW . 'Respawning in ' . TextFormat::RED . '1' . TextFormat::YELLOW . ' second!');
                    $this->time--;
                } else {
                    $this->player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . 'RESPAWNED!', '', 0, 20, 20);
                    $this->player->setGamemode($this->arena->getGameSettings()->hasNoProtection() ? GameMode::SURVIVAL : GameMode::ADVENTURE);

                    $this->player->getInventory()->setItem(Items::INFECTED_SWORD_SLOT, Items::getKnife($this->player));
                    $this->player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 9999, 1));

                    $this->player->teleport($this->arena->getPlugin()->getArenaConfig()->getRandomSpawn($this->arena));
                    $this->getHandler()->cancel();
                }
            }
        }, 20);
    }

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        /** @var PlayerInventory $inventory */
        $inventory = $event->getInventory();
        /** @var Player $player */
        $player = $inventory->getHolder();

        $entity = $event->getOrigin();
        $item = $event->getItem();

        if ($item->equals(Items::getResourceItem())) {
            $resourceItem = $inventory->getItem(Items::RESOURCE_SLOT);
            if ($resourceItem->isNull()) {
                $resourceItem = Items::getResourceItem()->setCount(min($item->getCount(), 64));
            } else {
                $resourceItem->setCount(min($resourceItem->getCount() + $item->getCount(), 64));
            }

            if (!$this->getArena()->isInfected($player) && $resourceItem->getCount() >= 6) {
                $resourceItem->pop(6);

                $arrowItem = $inventory->getItem(Items::SURVIVOR_ARROW_SLOT);
                if ($arrowItem->isNull()) {
                    $arrowItem = VanillaItems::ARROW()->setCount(24);
                } else {
                    $arrowItem->setCount(min($arrowItem->getCount() + 24, 64));
                }
                $inventory->setItem(Items::SURVIVOR_ARROW_SLOT, $arrowItem);
                $player->sendTitle('§e+24 arrows!', '', 0, 60, 20);
            }

            $inventory->setItem(Items::RESOURCE_SLOT, $resourceItem);
            Utils::playSound($player, 'random.pop2', 0.4, 7);
        }
        $entity->flagForDespawn();
        $event->cancel();
    }

    public function onArenaQuit(Player $player): void
    {
        if (!$this->getArena()->isSpectator($player) && !$this->getArena()->isFinishing()) {
            if ($this->getArena()->isInfected($player)) {
                if ($this->getArena()->isRunning()) {
                    if ($this->getArena()->getAlpha() === $player) {
                        $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 6, CustomIcon::ALEX_HEAD . TextFormat::RED . 'Dead');
                        foreach ($this->getArena()->getInfected() as $infected) {
                            $infected->sendTitle(TextFormat::RED . 'The Alpha died', TextFormat::YELLOW . 'You will no longer respawn', 0, 60, 20);
                        }
                        foreach ($this->getArena()->getSurvivors() as $survivor) {
                            $survivor->sendTitle(TextFormat::RED . 'The Alpha died', TextFormat::YELLOW . 'Infected will no longer respawn', 0, 60, 20);
                        }

                        if ($this->getArena()->getAlphaDeaths() === 0) {
                            $player->getArmorInventory()->clearAll();
                        }
                    }

                    $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 8, CustomIcon::ZOMBIE_HEAD . TextFormat::RED . (count($this->getArena()->getInfected()) - 1));
                }

                $this->getArena()->removeInfected($player);
            } elseif ($this->getArena()->isRunning()) {
                $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 9, CustomIcon::STEVE_HEAD . TextFormat::GREEN . (count($this->getArena()->getSurvivors()) - 1));
            }
        }

        parent::onArenaQuit($player);
    }

    public function onEntityShootBow(EntityShootBowEvent $event): void
    {
        /** @var Bow $bow */
        $bow = $event->getBow();

        if ($bow->getDamage() === 0) {
            $bow->setDamage($bow->getMaxDurability() - 3);
        } else {
            $event->cancel();
        }
    }
}