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

namespace murdermystery\gamemodes\classic;

use libminigames\Arena;
use murdermystery\gamemodes\MMArenaListener;
use murdermystery\utils\Items;
use murdermystery\utils\MMKnife;
use murdermystery\utils\StatsData;
use murdermystery\utils\Utils;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Bow;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function min;

final class MMArenaListenerClassic extends MMArenaListener
{
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        /** @var Sword $item */
        $item = $event->getItem();

        if ($this->getArena()->isMurderer($player) && $item->equals(Items::getKnife($player))) {
            MMKnife::throwKnife($player);
            $item->setDamage($item->getMaxDurability() - 3);

            $player->getInventory()->setItemInHand($item);
        }

        parent::onPlayerItemUse($event);
    }

    /**
     * @return MMArenaClassic
     */
    public function getArena(): Arena
    {
        /** @var MMArenaClassic $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        parent::onEntityDamageByEntity($event);

        if (!$event->isCancelled()) {
            $entity = $event->getEntity();
            /** @var Player $damager */
            $damager = $event->getDamager();

            $statsData = $this->getArena()->getStatsData();

            if ($entity instanceof Player) {
                if ($event instanceof EntityDamageByChildEntityEvent) {
                    $child = $event->getChild();
                    if ($child instanceof Arrow) {
                        if ($this->getArena()->isInnocent($entity) && $this->getArena()->isInnocent($damager)) {
                            $entity->sendTitle('§cYOU DIED!', '§eA player killed you!', 0, 100, 20);
                            $entity->sendMessage('§cYOU DIED! §eA player killed you!');
                            $this->onPlayerDeath($entity);

                            $damager->sendTitle('§cYOU DIED!', '§eYou killed an innocent player!', 0, 100, 20);
                            $damager->sendMessage('§cYOU DIED! §eYou killed an innocent player!');
                            $this->onPlayerDeath($damager);

                            $event->cancel();
                        } elseif ($this->getArena()->isMurderer($damager)) {
                            Utils::playSound($damager, 'random.levelup', 1, 1);
                            $this->onPlayerDeath($entity);

                            $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                            $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                            $statsData->addKill($damager, $entity, StatsData::MM_BOW_KILLS);

                            $entity->sendTitle('§cYOU DIED!', '§eThe Murderer stabbed you!', 0, 100, 20);
                            $entity->sendMessage('§cYOU DIED! §eThe Murderer stabbed you!');
                            $event->cancel();
                        } elseif ($damager !== $entity) {
                            $this->onPlayerDeath($entity);
                            if ($this->getArena()->isMurderer($entity)) {
                                $this->getArena()->broadcastMessage(TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($damager) . TextFormat::YELLOW . ' has stopped the murderer, ' . TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($entity) . TextFormat::YELLOW . '!', true);
                                foreach ($this->getArena()->getPlayers() as $player) {
                                    if ($this->getArena()->isMurderer($player)) {
                                        $player->sendTitle('§cYOU LOSE!', '§6You got killed!', 0, 100, 20);
                                        $player->sendMessage('§cYOU LOSE! §6You got killed!');
                                    } else {
                                        $player->sendTitle('§aYOU WIN!', '§6The Murderer has been stopped!', 0, 100, 20);
                                        $player->sendMessage('§aYOU WIN! §6The Murderer has been stopped!');
                                        Utils::playSound($player, 'random.levelup', 1, 1);
                                    }
                                }

                                $this->getArena()->setMurderer();
                                $this->getArena()->setMurdererKilled($damager);
                                $this->getArena()->setWinners($this->getArena()->getInnocents());

                                $statsData->addKill($damager, $entity, StatsData::KILLS);
                                $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                                $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                                $statsData->addKill($damager, $entity, StatsData::MM_BOW_KILLS);

                                foreach ($this->getArena()->getWorld()->getEntities() as $entity) {
                                    if ($entity instanceof MMKnife) {
                                        $entity->close();
                                    }
                                }
                            }
                            $event->cancel();
                        }
                    } elseif ($child instanceof MMKnife) {
                        Utils::playSound($damager, 'random.levelup', 1, 1);
                        $this->onPlayerDeath($entity);

                        $statsData->addKill($damager, $entity, StatsData::KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                        $statsData->addKill($damager, $entity, StatsData::MM_THROW_KNIFE_KILLS);

                        $entity->sendTitle('§cYOU DIED!', '§eThe Murderer threw their knife at you!', 0, 100, 20);
                        $entity->sendMessage('§cYOU DIED! §eThe Murderer threw their knife at you!');

                        $event->cancel();
                    }
                } elseif ($this->getArena()->isMurderer($damager) && $damager->getInventory()->getItemInHand()->equals(Items::getKnife($damager), false, false)) {
                    Utils::playSound($damager, 'random.levelup', 1, 1);
                    $this->onPlayerDeath($entity);

                    $statsData->addKill($damager, $entity, StatsData::KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_MODE_KILLS);
                    $statsData->addKill($damager, $entity, StatsData::MM_KNIFE_KILLS);

                    $entity->sendTitle('§cYOU DIED!', '§eThe Murderer stabbed you!', 0, 100, 20);
                    $entity->sendMessage('§cYOU DIED! §eThe Murderer stabbed you!');
                    $event->cancel();
                }
            } elseif ($entity instanceof ItemEntity) {
                if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                    $bowLoc = $this->getArena()->getPlugin()->getArenaConfig()->getRandomSpawn($this->getArena());

                    $entity->teleport($bowLoc);
                    foreach ($this->getArena()->getInnocents() as $innocent) {
                        if ($innocent->isConnected()) {
                            Utils::sendCompassPosition($innocent, $bowLoc);
                        }
                    }
                    $event->cancel();
                }
            }
        }
    }

    public function onPlayerDeath(Player $player, bool $spawnCorps = true): void
    {
        if ($this->getArena()->isInnocent($player)) {
            $this->getArena()->removeInnocent($player);

            if ($this->getArena()->isDetective($player)) {
                $bowLoc = ($spawnCorps ? $player->getLocation() : $this->getArena()->getPlugin()->getArenaConfig()->getRandomSpawn($this->getArena()));
                $this->getArena()->setDetective();
                $player->getWorld()->dropItem($bowLoc, Items::getDetectiveBow());
                foreach ($this->getArena()->getInnocents() as $innocent) {
                    if ($innocent->isConnected()) {
                        Utils::sendCompassPosition($innocent, $bowLoc);
                        $innocent->getInventory()->setItem(Items::COMPASS, Items::getBowLocator());
                    }
                }
                $this->getArena()->broadcastTitle('§6The Detective has been killed!', '§eFind the Bow for a chance to kill the Murderer.', 0, 60, 20);
                $this->getArena()->broadcastMessage('§6The Detective has been killed! §eFind the Bow for a chance to kill the Murderer.', true);

                $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 5, CustomIcon::SEARCH . TextFormat::GREEN . 'Dead');
            }
        } elseif ($this->getArena()->isMurderer($player)) {
            $this->getArena()->setMurderer();
            $this->getArena()->setWinners($this->getArena()->getInnocents());

            foreach ($this->getArena()->getInnocents() as $p) {
                $p->sendTitle('§aYOU WIN!', '§6The Murderer has been stopped!', 0, 100, 20);
                $p->sendMessage('§aYOU WIN! §6The Murderer has been stopped!');
                Utils::playSound($p, 'random.levelup', 1, 1);
            }
        }

        $this->getArena()->getScoreboard()->setLine([$player], 10, CustomIcon::GAMEMODE . TextFormat::GREEN . 'Dead');

        parent::onPlayerDeath($player, $spawnCorps);
    }

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        /** @var PlayerInventory $inventory */
        $inventory = $event->getInventory();
        /** @var Player $player */
        $player = $inventory->getHolder();

        $entity = $event->getOrigin();
        $item = $event->getItem();

        if ($item->equals(Items::getResourceItem(), false, false)) {
            $resourceItem = $inventory->getItem(Items::RESOURCE_SLOT);

            $entity->flagForDespawn();

            $event->cancel();

            if ($resourceItem->isNull()) {
                $resourceItem = Items::getResourceItem()->setCount(min($item->getCount(), 64));
            } else {
                $resourceItem->setCount(min($resourceItem->getCount() + $item->getCount(), 64));
            }

            if (!$this->getArena()->isDetective($player) && $resourceItem->getCount() >= 10) {
                $resourceItem->pop(10);
                $inventory->setItem(Items::INNOCENT_BOW_SLOT, Items::getBow());

                if ($this->getArena()->isMurderer($player)) {
                    $slot = Items::MURDERER_ARROW_SLOT;
                } else {
                    $slot = Items::INNOCENT_ARROW_SLOT;
                }

                $arrowItem = $inventory->getItem($slot);
                if ($arrowItem->isNull()) {
                    $arrowItem = VanillaItems::ARROW();
                } else {
                    $arrowItem->setCount(min($arrowItem->getCount() + 1, 64));
                }
                $inventory->setItem($slot, $arrowItem);
                $player->sendTitle('§a+1 Bow Shoot!', '§eYou collected 10 gold and got an arrow!', 0, 60, 20);
            }

            $inventory->setItem(Items::RESOURCE_SLOT, $resourceItem);
            Utils::playSound($player, 'random.pop2', 0.4, 7);
        } elseif ($item->equals(Items::getDetectiveBow(), false, false)) {
            if ($this->getArena()->isInnocent($player)) {
                $entity->flagForDespawn();

                $event->cancel();

                $this->getArena()->setDetective($player);

                $player->getInventory()->setItem(Items::DETECTIVE_BOW_SLOT, Items::getDetectiveBow());
                $player->getInventory()->setItem(Items::DETECTIVE_ARROW_SLOT, VanillaItems::ARROW());

                foreach ($this->getArena()->getInnocents() as $innocent) {
                    $innocent->getInventory()->setItem(Items::COMPASS, VanillaItems::AIR());
                }
                $player->getInventory()->setItem(Items::INNOCENT_BOW_SLOT, VanillaItems::AIR());

                Utils::playSound($player, 'random.pop2', 0.4, 7);
                $player->sendTitle('§aYou picked up the Bow!', '§6GOAL:§e Find and kill the murderer!', 0, 60, 20);

                foreach ($this->getArena()->getPlayers() as $p) {
                    if ($p->getName() !== $player->getName()) {
                        $p->sendTitle(' ', '§eA player has picked up the Bow!', 0, 60, 20);
                        $p->sendMessage('§eA player has picked up the Bow!');
                    }
                }
                $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 5, CustomIcon::SEARCH . TextFormat::GREEN . 'Alive');
            } else {
                $event->cancel();
            }
        } else {
            $event->cancel();
        }
    }

    public function onEntityShootBow(EntityShootBowEvent $event): void
    {
        /** @var Bow $bow */
        $bow = $event->getBow();

        if ($bow->equals(Items::getDetectiveBow())) {
            $bow->setDamage($bow->getMaxDurability() - 3);
        } else {
            $bow->setDamage(0);
        }
    }

    public function onArenaQuit(Player $player): void
    {
        if (!$this->getArena()->isFinishing()) {
            if ($this->getArena()->isInnocent($player)) {
                if ($this->getArena()->isDetective($player)) {
                    $this->getArena()->setDetective();

                    if ($this->getArena()->isRunning()) {
                        $this->getArena()->broadcastTitle('§6The Detective has left!', '§eFind the Bow for a chance to kill the Murderer.', 0, 60, 20);
                        $this->getArena()->broadcastMessage('§6The Detective has left! §eFind the Bow for a chance to kill the Murderer.', true);

                        $playerPos = $player->getLocation();
                        $this->getArena()->getWorld()->dropItem($playerPos, Items::getDetectiveBow());

                        foreach ($this->getArena()->getInnocents() as $innocent) {
                            Utils::sendCompassPosition($innocent, $playerPos);
                            $innocent->getInventory()->setItem(Items::COMPASS, Items::getBowLocator());
                        }
                    }
                }

                $this->getArena()->removeInnocent($player);
            } elseif ($this->getArena()->isMurderer($player)) {
                if ($this->getArena()->isRunning()) {
                    foreach ($this->getArena()->getPlayers() as $p) {
                        if (!$this->getArena()->isMurderer($p)) {
                            $p->sendTitle('§aYOU WIN!', '§6The Murderer has been stopped!', 0, 100, 20);
                            $p->sendMessage('§aYOU WIN! §6The Murderer has been stopped!');
                            Utils::playSound($p, 'random.levelup', 1, 1);
                        }
                    }

                    $this->getArena()->setWinners($this->getArena()->getInnocents());
                }

                $this->getArena()->setMurderer();
            }
        }

        parent::onArenaQuit($player);
    }
}