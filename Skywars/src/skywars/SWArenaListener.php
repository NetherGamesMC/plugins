<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars;

use libminigames\Arena;
use libminigames\ArenaListener;
use libminigames\utils\AutoUpgrader;
use libminigames\utils\StatsData as StatsDataAlias;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\player\store\categories\SWStore;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\tile\Chest;
use pocketmine\block\TNT;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils as PMUtils;
use skywars\entities\PrimedTNT;
use skywars\entities\SWEnderPearl;
use skywars\entities\SWZombie;
use skywars\utils\Forms;
use skywars\utils\Items;
use skywars\utils\StatsData;
use skywars\utils\Utils;
use function cos;
use function count;
use function preg_replace;
use function sin;
use function str_replace;
use const M_PI;

class SWArenaListener extends ArenaListener
{
    public const MAX_ARROW_DAMAGE = 10;

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();

        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block->getTypeId() === BlockTypeIds::CHEST) {
            if ($arena->getType() !== SWArena::TYPE_LUCKY_BLOCK) {
                $position = $block->getPosition();
                $tile = $arena->getWorld()->getTile($position);

                if ($tile instanceof Chest) {
                    $chestManager = $arena->getChestManager();

                    /** @var ChestInventory $inventory */
                    $inventory = $tile->getInventory();
                    $playerInventory = $player->getInventory();

                    $chestManager->openChest($inventory);

                    foreach ($inventory->getContents() as $item) {
                        if ($playerInventory->canAddItem($item)) {
                            $playerInventory->addItem($item);
                        } else {
                            $position->getWorld()->dropItem($position->add(0.5, 0.5, 0.5), $item);
                        }
                    }

                    $inventory->clearAll();
                    $chestManager->closeChest($inventory);
                }
            } else {
                $player->sendMessage("§cChests disabled in lucky block!");
            }

            $event->cancel();
        } elseif ($block->getPosition()->getY() > ($plugin->getArenaConfig()->getTeamSpawn($arena, $arena->getTeam($player)->getId())->getY() + 15)) {
            $player->sendMessage('§cYou have reached the height limit.');
            $event->cancel();
        } else {
            $arena->getStatsData()->addValue($player, StatsData::SW_BLOCKS_BROKEN);
        }
    }

    /**
     * @return SWArena
     */
    public function getArena(): Arena
    {
        /** @var SWArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        $arena = $this->getArena();
        $inventory = $event->getInventory();

        if ($inventory instanceof ChestInventory) {
            $arena->getChestManager()->closeChest($inventory);
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $arena = $this->getArena();
        $player = $event->getPlayer();
        $inventory = $event->getInventory();

        if ($inventory instanceof BlockInventory && $player->getGamemode() === GameMode::ADVENTURE) {
            $event->cancel();
            return;
        }

        if ($inventory instanceof ChestInventory) {
            if ($arena->getType() !== SWArena::TYPE_LUCKY_BLOCK) {
                $arena->getChestManager()->openChest($inventory);
            } else {
                $player->sendMessage("§cChests disabled in lucky block!");
                $event->cancel();
            }
        }
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();

        if ($item->equals(Items::getKitSelector())) {
            match (true) {
                $arena->getGameSettings()->hasFreeKits() => Forms::sendFreeKitSelectionForm($player, $arena),
                default => $plugin->getEssentials()->getPlayerManager()->getStore()->getCategory(SWStore::ID)->sendForm($player)
            };
        }

        return parent::onItemInteract($player, $item);
    }

    public function onPlayerChat(NGChatEvent $event): void
    {
        $arena = $this->getArena();
        $player = $event->getPlayer();

        if ($arena->isSpectator($player)) {
            $event->setDisplayName(TextFormat::clean($player->getDisplayName()));
            $event->setRecipients($arena->getSpectators());
            $event->setPrefix('§7Dead Chat > ');
            $event->setStaffPrefix('§7Dead Chat Relay > ');
            $event->setSplitter(': ');
        } elseif ($arena->isSoloGame()) {
            $event->setDisplayName($player->getDisplayName());
        } else {
            $team = $arena->getTeam($player);
            $event->setDisplayName($team->getPlayerName($player));

            if ($arena->isRunning()) {
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

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $team = $arena->getTeam($player);
            $cause = $event->getCause();
            $essentials = $plugin->getEssentials();

            if ($event instanceof EntityDamageByChildEntityEvent) {
                $child = $event->getChild();
                if ($child instanceof Arrow && $event->getFinalDamage() > self::MAX_ARROW_DAMAGE) {
                    $event->setBaseDamage(self::MAX_ARROW_DAMAGE);
                }
            }

            if ($player->getEffects()->has(VanillaEffects::WATER_BREATHING())) {
                $event->cancel();
            } elseif ($event->getFinalDamage() >= $event->getEntity()->getHealth()) {
                $event->cancel();

                $combatLogger = $essentials->getCombatLogger();
                $log = $combatLogger->getLog($player);
                foreach ($log->getAssists() as $assistName) {
                    $arena->addAssist($assistName);
                }

                switch ($cause) {
                    case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                    case EntityDamageEvent::CAUSE_PROJECTILE:
                    case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                    case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
                        if ($event instanceof EntityDamageByEntityEvent) {
                            $damager = $event->getDamager();
                            if ($damager instanceof Player) {
                                if ($arena->isSoloGame()) {
                                    $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $plugin->getRandomKillMessage($event->getCause())));
                                } else {
                                    $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $arena->getTeam($damager)->getPlayerName($damager)], $plugin->getRandomKillMessage($event->getCause())));
                                }

                                $arena->addKill($damager, $player);
                                $this->onPlayerDeath($player, $team);
                            } else {
                                if ($damager !== null && ($owner = $damager->getOwningEntity()) instanceof Player && $this->getArena()->isInArena($owner)) {
                                    $ownerTeam = $this->getArena()->getTeam($owner);

                                    $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $ownerTeam->getPlayerName($owner)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                                    $this->getArena()->addKill($owner, $player);
                                } else {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage(-1)), true);
                                }

                                $this->onPlayerDeath($player, $team);
                            }
                        }
                        break;
                    case EntityDamageEvent::CAUSE_FALL:
                    case EntityDamageEvent::CAUSE_VOID:
                    case EntityDamageEvent::CAUSE_LAVA:
                        if (($damager = $combatLogger->getLatestHit($player)) !== null && $arena->isInArena($damager)) {
                            if ($arena->isSoloGame()) {
                                $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $plugin->getRandomKillMessage($event->getCause(), true)));
                            } else {
                                $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $arena->getTeam($damager)->getPlayerName($damager)], $plugin->getRandomKillMessage($event->getCause(), true)));
                            }

                            $arena->addKill($damager, $player);

                            if ($cause === EntityDamageEvent::CAUSE_VOID && !$arena->isSpectator($damager)) {
                                $drops = [];

                                foreach ($player->getDrops() as $item) {
                                    $drops[] = AutoUpgrader::getInstance()->upgradeItem($damager, $item);
                                }

                                $damager->getInventory()->addItem(...$drops);
                            }
                        } elseif ($arena->isSoloGame()) {
                            $arena->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $plugin->getRandomKillMessage($event->getCause())));
                        } else {
                            $arena->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $plugin->getRandomKillMessage($event->getCause())));
                        }

                        $this->onPlayerDeath($player, $team, $cause === EntityDamageEvent::CAUSE_FALL);
                        break;
                    default:
                        if ($arena->isSoloGame()) {
                            $arena->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $plugin->getRandomKillMessage($event->getCause())));
                        } else {
                            $arena->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $plugin->getRandomKillMessage($event->getCause())));
                        }

                        $this->onPlayerDeath($player, $team, false);
                        break;
                }

            } elseif ($cause === EntityDamageEvent::CAUSE_VOID) {
                $event->cancel();

                if (($damager = $essentials->getCombatLogger()->getLatestHit($player)) !== null && $arena->isInArena($damager)) {
                    if ($arena->isSoloGame()) {
                        $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $plugin->getRandomKillMessage($event->getCause(), true)));
                    } else {
                        $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $arena->getTeam($damager)->getPlayerName($damager)], $plugin->getRandomKillMessage($event->getCause(), true)));
                    }

                    $arena->addKill($damager, $player);
                } elseif ($arena->isSoloGame()) {
                    $arena->broadcastMessage(str_replace('{PLAYER}', $player->getNameTag(), $plugin->getRandomKillMessage($event->getCause())));
                } else {
                    $arena->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $plugin->getRandomKillMessage($event->getCause())));
                }

                $this->onPlayerDeath($player, $team, false);
            }
        }
    }

    public function onPlayerDeath(Player $player, SWTeam $team, bool $spawnCorps = true): void
    {
        $arena = $team->getArena();

        $statsData = $arena->getStatsData();
        $statsData->addValue($player, StatsDataAlias::DEATHS);
        if ($arena->isDuelsGame()) {
            $statsData->addValue($player, StatsData::DUELS_DEATHS);
        } else {
            $statsData->addValue($player, StatsData::SW_DEATHS);
            $statsData->addValue($player, StatsData::SW_MODE_DEATHS);
            $statsData->addValue($player, StatsData::SW_MODE_TYPE_DEATHS);
        }

        $player->sendTitle('§l§cYOU DIED!', '§7You are now a spectator.', 0, 100, 20);

        if ($spawnCorps) {
            foreach ($player->getDrops() as $item) {
                if (!$item->equals(Items::getKitSelector())) {
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }

            $arena->addSpectator($player);
            Utils::spawnPlayerHuman($player);
        } else {
            $arena->addSpectator($player);
            $player->teleport($player->getWorld()->getSafeSpawn());
        }

        if (!$arena->isDuelsGame()) {
            $arena->getScoreboard()->setLine($arena->getPlayers(), 8, CustomIcon::PLAYERS_TINY . ' ' . TextFormat::GREEN . count($arena->getAlivePlayers()));
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        if ($event->getItem()->equals(Items::getKitSelector())) {
            $event->cancel();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $arena = $this->getArena();
        $plugin = $arena->getPlugin();

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            $position = new Vector3($x, $y, $z);

            if ($block->getTypeId() === BlockTypeIds::CHEST) {
                $event->cancel();
            } elseif ($block->getPosition()->getY() > ($plugin->getArenaConfig()->getTeamSpawn($arena, $arena->getTeam($player)->getId())->getY() + 15)) {
                $player->sendMessage('§cYou have reached the height limit.');
                $event->cancel();
            } elseif ($block instanceof TNT) {
                $motion = (new Random())->nextSignedFloat() * M_PI * 2;

                $tnt = new PrimedTNT(Location::fromObject($position->add(0.5, 0, 0.5), $player->getWorld()));
                $tnt->setMotion(new Vector3(-sin($motion) * 0.02, 0.2, -cos($motion) * 0.02));
                $tnt->setOwningEntity($player);
                $tnt->spawnToAll();

                $item->pop();

                $player->getInventory()->setItemInHand($item);
                $event->cancel();
            } else {
                $arena->getStatsData()->addValue($player, StatsData::SW_BLOCKS_PLACED);
            }
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $isCorrupted = $event->getItem()->getNamedTag()->getByte('corrupted', 0) === 1;

        if ($isCorrupted && !$this->getArena()->canUseCorrupted()) {
            $event->getPlayer()->sendMessage('§cYou cannot use corrupted items in the first 20 seconds of the game.');
            $event->cancel();
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();

        if ($item->getTypeId() === ItemTypeIds::ZOMBIE_SPAWN_EGG) {
            $blockReplace = $event->getBlock()->getSide($event->getFace());

            $zombie = new SWZombie(Location::fromObject($blockReplace->getPosition()->add(0.5, 0, 0.5), $player->getWorld(), PMUtils::getRandomFloat() * 360, 0));
            $zombie->setOwningEntity($player);
            $zombie->spawnToAll();

            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }
    }

    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();

        $entity = $event->getEntity();
        $player = $event->getEntity()->getOwningEntity();
        $statsData = $arena->getStatsData();

        if ($player instanceof Player && $plugin->getArena($player) !== null) {
            if ($entity instanceof EnderPearl) {
                $statsData->addValue($player, StatsData::SW_EPEARLS_THROWN);

                $event->cancel();

                $enderPearl = new SWEnderPearl($entity->getLocation(), $entity->getOwningEntity(), $entity->saveNBT());
                $enderPearl->spawnToAll();

                $inventory = $player->getInventory();
                $item = $inventory->getItemInHand();
                $item->pop();
                $inventory->setItemInHand($item);
            } elseif ($entity instanceof Egg) {
                $statsData->addValue($player, StatsData::SW_EGGS_THROWN);
            }
        }
    }
}