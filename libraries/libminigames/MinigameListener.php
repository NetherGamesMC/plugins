<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames;

use libminigames\events\MinigameQuitEvent;
use libminigames\events\MinigameStartEvent;
use libminigames\utils\Forms;
use libminigames\utils\Items;
use libminigames\utils\StatsData;
use libVanilla\entity\object\Fireball;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\events\PlayerInputChangeEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\command\utils\CommandStringHelper;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function array_shift;
use function count;
use function in_array;
use function round;

/**
 * An arena <em>global public listener<em>.
 *
 * <p>This method implies that all events that are called will be passed here. Execution of
 * the code help developers to redirect events to a specific arena listeners, {@see ArenaListener}.
 * The events passed will be filtered, in which the condition should be, if the player is in the arena
 * and only if the arena is currently running.
 *
 * <p>While attempting to override this listener class for you to call your own listener objects,
 * try not to override any functions that has already being defined here, instead use {@see ArenaListener}
 * to retrieve such events.
 *
 * @package libminigames
 */
class MinigameListener implements Listener
{

    /*
     * There is no such reason to document this code. Its pretty self-explanatory.
     * Try to understand how this model works and the relation within this class and an ArenaListener.
     */

    private const BLOCKED_COMMANDS = [
        'gamemode',
        'give',
        'tp',
        'track',
        'effect'
    ];

    /** @var Minigame */
    private Minigame $plugin;

    public function __construct(Minigame $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockBreak($event);
            } else {
                $event->cancel();
            }
        }
    }

    public function getPlugin(): Minigame
    {
        return $this->plugin;
    }

    /**
     * @param BlockUpdateEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockUpdate($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param StructureGrowEvent $event
     *
     * @priority NORMAL
     */
    public function onStructureGrow(StructureGrowEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onStructureGrow($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param BlockBurnEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockBurn(BlockBurnEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockBurn($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerInputChangeEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerInputChange(PlayerInputChangeEvent $event): void
    {
        $player = $event->getPlayer();

        if ((($arena = $this->getPlugin()->getArena($player)) !== null) && $arena->isTouchOnly() && $event->getNewInputMode() !== InputMode::TOUCHSCREEN) {
            $player->kick(TextFormat::RED . "You can't use another input device while playing in touch-only mode!");
        }
    }

    /**
     * @param BlockGrowEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockGrow(BlockGrowEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockGrow($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param BlockFormEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockForm(BlockFormEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockForm($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param BlockSpreadEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockSpread($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param LeavesDecayEvent $event
     *
     * @priority NORMAL
     */
    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            $event->cancel();

            if ($arena->isRunning()) {
                $arena->getListener()->onLeavesDecay($event);
            }
        }
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority NORMAL
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        $plugin = $this->getPlugin();
        if (!NGEssentials::isInDevelopmentMode() && $plugin->isStandAloneGame()) {
            $player = $event->getPlayer();
            $ess = $plugin->getEssentials();

            if ($ess->getPlayerData()->getString($player, PlayerData::TRACK) === '') {
                $playerManager = $ess->getPlayerManager();

                if (($party = ($partyManager = $playerManager->getSocialManager()->getPartyManager())->getParty($player, false)) === null) {
                    $plugin->joinArena($player);
                } else {
                    // +1 player might not be added to logged in players yet
                    $players = $partyManager->getPlayers($party);
                    $isAlreadyInParty = in_array($player, $players, true);
                    $onlinePlayers = count($players) + ($isAlreadyInParty ? 0 : 1);

                    if ($onlinePlayers === count($party->getAll())) {
                        if ($isAlreadyInParty) {
                            /** @var Player $leader */
                            $leader = $party->getLeader();

                            $plugin->joinArena($leader);
                        } else {
                            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($plugin, $party): void {
                                $leader = $party->getLeader();

                                if ($leader instanceof Player && $leader->isConnected()) {
                                    $plugin->joinArena($leader);
                                }
                            }), 1);
                        }
                    } else {
                        $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($plugin, $player, $partyManager): void {
                            if (!$player->isConnected()) {
                                return;
                            }

                            if (($party = $partyManager->getParty($player)) === null) {
                                if ($plugin->getArena($player) === null) {
                                    $plugin->joinArena($player);
                                }
                            } else {
                                /** @var Player $leader */
                                $leader = $party->getLeader();
                                if ($plugin->getArena($leader) === null) {
                                    $partyManager->cleanMembers($party);

                                    $leader->sendMessage(TextFormat::RED . "One of your party members hasn't connected to this server. Joining the game...");
                                    $plugin->joinArena($leader);
                                }
                            }
                        }), 3 * 20);
                    }
                }
            }
        }
    }

    /**
     * @param PlayerKickEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerKickEvent(PlayerKickEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            $arena->removePlayer($player, MinigameQuitEvent::DISCONNECT_KICK);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            $arena->removePlayer($player, MinigameQuitEvent::DISCONNECT);
        }
    }

    /**
     * @param InventoryCloseEvent $event
     *
     * @priority NORMAL
     */
    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null && $arena->isRunning()) {
            $arena->getListener()->onInventoryClose($event);
        }
    }

    /**
     * @param ProjectileLaunchEvent $event
     *
     * @priority NORMAL
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        if ((($arena = $this->getPlugin()->getArenaByWorld($event->getEntity()->getWorld())) !== null) && $arena->isRunning()) {
            $arena->getListener()->onProjectileLaunch($event);
        }
    }

    /**
     * @param EntityPreExplodeEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityPreExplode(EntityPreExplodeEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getEntity()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityPreExplode($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param ProjectileHitBlockEvent $event
     *
     * @priority NORMAL
     */
    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        if ((($arena = $this->getPlugin()->getArenaByWorld($event->getEntity()->getWorld())) !== null) && $arena->isRunning()) {
            $arena->getListener()->onProjectileHitBlock($event);
        }
    }

    /**
     * @param InventoryOpenEvent $event
     *
     * @ignoreCancelled
     */
    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onInventoryOpen($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority NORMAL
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($player = $event->getTransaction()->getSource())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onInventoryTransaction($event);
            } else {
                $event->cancel();
            }

            if ($event->isCancelled()) {
                $cursorInventory = $player->getCursorInventory();
                $cursorInventory->setItem(0, $cursorInventory->getItem(0));
            }
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            if (!Utils::hasClassicUI($player) && $this->onItemInteract($arena, $player, $event->getItem())) {
                return;
            }

            if ($arena->isRunning()) {
                $arena->getListener()->onPlayerItemHeld($event);
            }
        }
    }

    public function onItemInteract(Arena $arena, Player $player, Item $item): bool
    {
        if ($arena->isWaiting() || $arena->isStarting()) {
            if ($item->equals(Items::getMapSelectionPaper())) {
                Forms::sendMapSelector($player, $arena);
            } else if ($item->equals(Items::getQuitBed())) {
                $arena->removePlayer($player, MinigameQuitEvent::LEAVE);
            } else if ($arena instanceof TeamArena && $item->equals(Items::getTeamSelectionWool($arena->getTeam($player)->getDyeColor()))) {
                Forms::sendTeamSelector($player, $arena);
            } else {
                return $arena->getListener()->onItemInteract($player, $item);
            }

            return true;
        }

        if ($arena->isSpectator($player)) {
            if ($item->equals(Items::getReplayPaper(), false)) {
                $partyManager = $this->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();

                if ($partyManager->isInParty($player) && !$partyManager->isPartyCreator($player)) {
                    $player->sendMessage('§cYou can\'t join another game while you\'re in a party. Wait for your party host to decide when to play again!');
                } else {
                    $mode = $this->getPlugin()->getModes()[$arena->getModeId()];
                    $this->getPlugin()->requeuePlayer($player, $arena, $mode);
                }
            } elseif ($item->equals(Items::getQuitBed())) {
                $arena->removePlayer($player, MinigameQuitEvent::END);
            } elseif ($item->equals(Items::getSpectatorCompass(), false)) {
                Forms::sendTeleporter($player, $arena);
            } else {
                return $arena->getListener()->onItemInteract($player, $item);
            }

            return true;
        }

        return $arena->getListener()->onItemInteract($player, $item);
    }

    /**
     * @param EntityCombustEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityCombust(EntityCombustEvent $event): void
    {
        $player = $event->getEntity();

        if (($player instanceof Player) && ($arena = $this->getPlugin()->getArena($player)) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityCombust($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityExplodeEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityExplode(EntityExplodeEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getEntity()->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityExplode($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param BlockMeltEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockMelt(BlockMeltEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getBlock()->getPosition()->getWorld())) !== null) {
            $event->cancel();

            if ($arena->isRunning()) {
                $arena->getListener()->onBlockMelt($event);
            }
        }
    }


    /**
     * @param EntityEffectAddEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityEffectAdd(EntityEffectAddEvent $event): void
    {
        $entity = $event->getEntity();

        if (($arena = $this->getPlugin()->getArenaByWorld($entity->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityEffectAdd($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityEffectRemoveEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityEffectRemove(EntityEffectRemoveEvent $event): void
    {
        $entity = $event->getEntity();

        if (($arena = $this->getPlugin()->getArenaByWorld($entity->getWorld())) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityEffectRemove($event);
            }
        }
    }

    /**
     * @param PlayerBucketEmptyEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event): void
    {
        if ((($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) && $arena->isRunning()) {
            $arena->getListener()->onPlayerBucketEmpty($event);
        }
    }

    /**
     * @param PlayerChangeSkinEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {
        if ((($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) && $arena->isRunning()) {
            $arena->getListener()->onPlayerChangeSkin($event);
        }
    }

    /**
     * @param EntityShootBowEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityShootBow(EntityShootBowEvent $event): void
    {
        $player = $event->getEntity();

        if (($player instanceof Player) && ($arena = $this->getPlugin()->getArena($player)) !== null && $arena->isRunning()) {
            $arena->getListener()->onEntityShootBow($event);
        }
    }

    /**
     * @param EntityItemPickupEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        $player = $event->getEntity();

        if (($player instanceof Player) && ($arena = $this->getPlugin()->getArena($player)) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityItemPickup($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param CraftItemEvent $event
     *
     * @priority NORMAL
     */
    public function onCraftItem(CraftItemEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($player = $event->getPlayer())) !== null) {
            if ($arena->isSpectator($player)) {
                $event->cancel();
            } elseif ($arena->isRunning()) {
                $arena->getListener()->onCraftItem($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onPlayerInteract($event);
            } else {
                $event->cancel();
            }
        }
    }


    /**
     * @param PlayerItemUseEvent $event
     *
     * @priority NORMAL
     * @handleCancelled
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null && (!$event->isCancelled() || $arena->isSpectator($player))) {
            if ($this->onItemInteract($arena, $player, $event->getItem())) {
                $event->cancel();
            } elseif ($arena->isRunning()) {
                $arena->getListener()->onPlayerItemUse($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param NGChatEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerChat(NGChatEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($event->getPlayer())) !== null) {
            $arena->getListener()->onPlayerChat($event);
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArena($player = $event->getPlayer())) !== null) {
            if ($arena->isSpectator($player)) {
                $event->cancel();
            } elseif ($arena->isRunning()) {
                $arena->getListener()->onPlayerDropItem($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof Player) {
            if (($arena = $this->getPlugin()->getArena($player)) !== null) {
                if ($arena->isRunning()) {
                    if ($arena->isSpectator($player) || ($arena instanceof TeamArena && $arena->getTeamNull($player) === null)) {
                        if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                            $player->teleport($arena->getWorld()->getSpawnLocation());
                        }

                        $event->cancel();
                    } elseif ($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if ($damager instanceof Player) {
                            if ($arena->isSpectator($damager)) {
                                $event->cancel();
                                return;
                            }

                            if ($arena instanceof TeamArena) {
                                if (($damagerTeam = $arena->getTeamNull($damager)) === null) {
                                    $event->cancel();
                                    return;
                                }

                                if ($damagerTeam === $arena->getTeam($player)) {
                                    $event->cancel();
                                    return;
                                }
                            }
                        }

                        $arena->getListener()->onEntityDamageByEntity($event);
                    }

                    if (!$event->isCancelled()) {
                        $arena->getListener()->onEntityDamage($event);

                        if (!$event->isCancelled() && $event instanceof EntityDamageByEntityEvent && $arena instanceof TeamArena) {
                            $damager = $event->getDamager();

                            if ($damager instanceof Player) {
                                $playerName = $arena->getTeam($player)->getPlayerName($player);

                                if ($event instanceof EntityDamageByChildEntityEvent) {
                                    $child = $event->getChild();
                                    /** @var NGPlayer $damager */
                                    if ($child instanceof Arrow) {
                                        $damager->playSound('random.orb');
                                        $damager->sendMessage($playerName . TextFormat::YELLOW . ' is on ' . TextFormat::RED . round(($player->getHealth() - $event->getFinalDamage()) / 2, 1) . CustomIcon::HEART);
                                    } else if ($child instanceof Fireball) {
                                        $damager->playSound('random.orb');
                                        $distance = $player->getPosition()->distance($damager->getPosition());
                                        if ($distance >= 50) {
                                            Translator::sendMessage($damager, 'fireball.perfect.hit', Translator::TYPE_SUCCESS, ...['playerName' => $playerName, 'blocks' => (string)floor($distance)]);
                                            if ($distance >= 85) {
                                                $arena->getStatsData()->addValue($damager->getName(), StatsData::PERFECT_SHOTS);
                                                $damager->playSound('mob.enderdragon.death');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                        $player->teleport($arena->getWaitingLobbySpawn());
                    }

                    $event->cancel();
                }
            }
        } elseif (($arena = $this->getPlugin()->getArenaByWorld($player->getWorld())) !== null) {
            $arena->getListener()->onEntityDamage($event);
        }
    }

    /**
     * @param EntitySpawnEvent $event
     *
     * @priority NORMAL
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void
    {
        if (($arena = $this->getPlugin()->getArenaByWorld($event->getEntity()->getWorld())) !== null) {
            $entity = $event->getEntity();

            if (!$entity instanceof Player) {
                if ($arena->isRunning()) {
                    $arena->getListener()->onEntitySpawn($event);
                } else {
                    $entity->flagForDespawn();
                }
            }
        }
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player && (($arena = $this->getPlugin()->getArena($entity)) !== null) && $arena->isRunning()) {
            $arena->getListener()->onEntityTeleport($event);
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onBlockPlace($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        $player = $event->getPlayer();

        if ((($arena = $this->getPlugin()->getArena($player)) !== null)) {
            if ($arena->isSpectator($player)) {
                $event->cancel();
            } elseif ($arena->isRunning()) {
                $arena->getListener()->onPlayerItemConsume($event);
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param CommandEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerCommand(CommandEvent $event): void
    {
        $player = $event->getSender();

        if (!($player instanceof Player) || NGEssentials::isInDevelopmentMode() || ($arena = $this->getPlugin()->getArena($player)) === null || $arena->isPrivateGame()) {
            return;
        }

        $args = CommandStringHelper::parseQuoteAware($event->getCommand());
        $commandMap = $player->getServer()->getCommandMap();
        $commandName = array_shift($args);

        if ($commandName !== null && ($command = $commandMap->getCommand($commandName)) !== null) {
            if (in_array($command->getName(), self::BLOCKED_COMMANDS)) {
                $player->sendMessage("§cYou can't run this command while you're in a " . $this->getPlugin()->getMinigameName() . ' match. Use §b/' . $this->getPlugin()->getMinigameTag() . ' quit §cto quit the game and run that command.');
                $event->cancel();
            }
        }
    }

    public function onMinigameStart(MinigameStartEvent $event): void
    {
        $player = $event->getPlayer();
        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            $arena->getListener()->onMinigameStart($event);
        }
    }

    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void
    {
        $player = $event->getEntity();
        if (!($player instanceof Player)) {
            return;
        }

        if (($arena = $this->getPlugin()->getArena($player)) !== null) {
            if ($arena->isRunning()) {
                $arena->getListener()->onEntityRegainHealth($event);
            } else {
                $event->cancel();
            }
        }
    }
}