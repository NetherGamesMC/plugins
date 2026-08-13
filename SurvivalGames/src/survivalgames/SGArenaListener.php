<?php

declare(strict_types=1);

namespace survivalgames;

use libminigames\Arena;
use libminigames\ArenaListener;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Bucket;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use survivalgames\utils\entity\Graveyard;
use survivalgames\utils\StatsData;
use function count;
use function str_replace;

class SGArenaListener extends ArenaListener
{
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            $this->getArena()->getBlockCollector()->addBlock(new Vector3($x, $y, $z));
        }
    }

    /**
     * @return SGArena
     */
    public function getArena(): Arena
    {
        /** @var SGArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block->getTypeId() === VanillaBlocks::CHEST()->getTypeId()) {
            $pos = $block->getPosition();
            $tile = $block->getPosition()->getWorld()->getTile($pos);

            if ($tile instanceof Chest) {
                $chestManager = $this->getArena()->getChestManager();

                /** @var ChestInventory $inventory */
                $inventory = $tile->getInventory();
                $playerInventory = $player->getInventory();

                $chestManager->openChest($inventory);

                foreach ($inventory->getContents() as $item) {
                    if ($playerInventory->canAddItem($item)) {
                        $playerInventory->addItem($item);
                    } else {
                        $pos->getWorld()->dropItem($pos->add(0.5, 0.5, 0.5), $item);
                    }
                }
                $inventory->clearAll();

                $chestManager->closeChest($inventory);
            }

            $event->cancel();
        } elseif (($blockCollector = $this->getArena()->getBlockCollector())->isBreakable($pos = $block->getPosition())) {
            $blockCollector->removeBlock($pos);
        } else {
            $event->cancel();
        }
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();

        if ($item instanceof Bucket) {
            // TODO: Tell dylan PlayerBucketFillEvent is broken.
            $pos = $event->getBlock()->getPosition();
            if (($blockCollector = $this->getArena()->getBlockCollector())->isBreakable($pos)) {
                $blockCollector->removeBlock($pos);
            } else {
                $event->cancel();
            }
        }
    }

    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event): void
    {
        $this->getArena()->getBlockCollector()->addBlock($event->getBlockClicked()->getPosition());
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $cause = $event->getCause();
            $ess = $this->getArena()->getPlugin()->getEssentials();

            if ($this->getArena()->hasFlags(SGArena::PLAYERS_INVINCIBLE)) {
                $event->cancel();
            } elseif ($event->getFinalDamage() >= $event->getEntity()->getHealth()) {
                $event->cancel();

                switch ($cause) {
                    case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                    case EntityDamageEvent::CAUSE_PROJECTILE:
                        if ($event instanceof EntityDamageByEntityEvent) {
                            $damager = $event->getDamager();

                            if ($damager instanceof Player) {
                                $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getDisplayName(), $damager->getDisplayName()], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);

                                $this->getArena()->addKill($damager, $player);
                                $this->onPlayerDeath($player);
                            }
                        }
                        break;
                    case EntityDamageEvent::CAUSE_FALL:
                    case EntityDamageEvent::CAUSE_VOID:
                    case EntityDamageEvent::CAUSE_LAVA:
                    case EntityDamageEvent::CAUSE_MAGIC:
                    case EntityDamageEvent::CAUSE_CONTACT:
                    case EntityDamageEvent::CAUSE_CUSTOM:
                        // The player was killed by cactus, cACtUs
                        if ($cause === EntityDamageEvent::CAUSE_CONTACT && $event instanceof EntityDamageByBlockEvent) {
                            $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                            $this->onPlayerDeath($player);
                            return;
                        }

                        if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                            $cause = ($event->getCause() === EntityDamageEvent::CAUSE_CUSTOM || $event->getCause() === EntityDamageEvent::CAUSE_CONTACT) ? EntityDamageEvent::CAUSE_ENTITY_ATTACK : $event->getCause();

                            $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getDisplayName(), $damager->getDisplayName()], $this->getArena()->getPlugin()->getRandomKillMessage($cause, true)), true);

                            $this->getArena()->addKill($damager, $player);

                            // Events kill drops, these "cause" can drops player's item (Graveyard).
                            $cause = $event->getCause();

                            $canDrop = $cause === EntityDamageEvent::CAUSE_CUSTOM
                                || ($cause === EntityDamageEvent::CAUSE_CONTACT && !($event instanceof EntityDamageByBlockEvent))
                                || $cause === EntityDamageEvent::CAUSE_FALL;

                            $this->onPlayerDeath($player, $canDrop);
                        } else {
                            if ($cause === EntityDamageEvent::CAUSE_CUSTOM) {
                                $currentEvent = $this->getArena()->getEventManager()->getEvent();
                                if ($currentEvent === SGEventManager::ACID_WATER) {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), "{PLAYER} §r§7was killed by an acid."));
                                } else if ($currentEvent === SGEventManager::METEOR_SHOWER) {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), "{PLAYER} §r§7was swept away by a storm of meteor showers!"));
                                } else if ($currentEvent === SGEventManager::CREEPER_MANIA) {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), "{PLAYER} §r§7is no match against a horde of creepers!"));
                                } else {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                                }
                                /** @phpstan-ignore-next-line - PHPStan doesn't know about the border and determines that these checks don't co-exist in PMMP */
                            } else if ($cause === EntityDamageEvent::CAUSE_CONTACT && !($event instanceof EntityDamageByBlockEvent)) {
                                $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), "{PLAYER} §r§7was killed by the border.")); // superlative?
                            } else {
                                $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                            }

                            $this->onPlayerDeath($player);
                        }
                        break;
                    default:
                        $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $player->getDisplayName(), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())), true);
                        $this->onPlayerDeath($player, false);
                        break;
                }
            }
        }
    }

    public function onPlayerDeath(Player $player, bool $spawnCorps = true): void
    {
        $arena = $this->getArena();
        $statsData = $arena->getStatsData();
        $statsData->addValue($player, StatsData::DEATHS);
        $statsData->addValue($player, StatsData::SG_DEATHS);

        $player->sendTitle('§l§cYOU DIED!', '§7You are now a spectator.');

        if ($spawnCorps) {
            $world = $player->getWorld();
            $location = $player->getLocation();

            $adj = $world->getSafeSpawn($location)->floor()->add(.5, 0, .5);

            $gv = new Graveyard(Location::fromObject($adj, $world));
            $gv->setGraveyardData($player, $arena);
            $gv->spawnToAll();

            $arena->addSpectator($player);
        } else {
            $arena->addSpectator($player);

            $player->teleport($player->getWorld()->getSafeSpawn());
        }

        $arena->getScoreboard()->setLine($this->getArena()->getPlayers(), 7, CustomIcon::PLAYERS_TINY . TextFormat::GREEN . count($this->getArena()->getAlivePlayers()) . '/' . $this->getArena()->getMaxSize());
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $inventory = $event->getInventory();

        if ($inventory instanceof ChestInventory) {
            $this->getArena()->getChestManager()->openChest($inventory);
        }
    }

    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        $inventory = $event->getInventory();

        if ($inventory instanceof ChestInventory) {
            $this->getArena()->getChestManager()->closeChest($inventory);
        }
    }

    public function onArenaQuit(Player $player): void
    {
        $arena = $this->getArena();

        // Gameplay: Check for player last combat after leaving a game. (They can quit the game after getting shot by bow or scared)
        if ($arena->isRunning() && !$arena->isSpectator($player)) {
            $ess = $arena->getPlugin()->getEssentials();
            if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $arena->isInArena($damager)) {
                $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getDisplayName(), $damager->getDisplayName()], $arena->getPlugin()->getRandomKillMessage(EntityDamageEvent::CAUSE_ENTITY_ATTACK, true)), true);

                $arena->addKill($damager, $player);
            }

            $statsData = $arena->getStatsData();
            $statsData->addValue($player, StatsData::DEATHS);
            $statsData->addValue($player, StatsData::SG_DEATHS);

            $world = $player->getWorld();
            $location = $player->getLocation();
            $adj = $world->getSafeSpawn($location)->floor()->add(.5, 0, .5);

            $gv = new Graveyard(Location::fromObject($adj, $world));
            $gv->setGraveyardData($player, $arena);
            $gv->spawnToAll();

            $arena->getScoreboard()->setLine($arena->getPlayers(), 7, CustomIcon::PLAYERS_TINY . TextFormat::GREEN . count($arena->getAlivePlayers()) . '/' . $arena->getMaxSize());

            $arena->removeDeathmatch($player);
        } elseif ($arena->isWaiting()) {
            $arena->removeTypeVote($player);
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
        } else {
            $event->setDisplayName($player->getDisplayName());
        }
    }
}