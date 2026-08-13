<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use bridges\menu\LayoutEditor;
use bridges\utils\Items;
use bridges\utils\StatsData;
use bridges\utils\Utils;
use libminigames\Arena;
use libminigames\ArenaListener;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\block\StainedHardenedClay;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Sword;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_map;
use function in_array;
use function preg_replace;
use function str_replace;

class BridgeArenaListener extends ArenaListener
{

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $arena = $this->getArena();

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if ($arena->phase === BridgeArena::PHASE_RESTART) {
                $event->cancel();
            } elseif (in_array(
                $player->getInventory()->getItemInHand()->getStateId(),
                array_map(fn(StainedHardenedClay $clay) => $clay->asItem()->getStateId(), $this->getArena()->getInteractableBlocks())
            )) {
                $block = $event->getBlock()->getSide($event->getFace());
                $pos = $block->getPosition();

                if (!$arena->getGameSettings()->hasNoWorldGuard()) {
                    if ($pos->getY() >= $arena->getBuildHeight()) {
                        $player->sendMessage(TextFormat::RED . 'You have reached the build limit.');
                        $event->cancel();
                        return;
                    }

                    foreach ($arena->getTeams() as $t) {
                        $teamSpawn = $t->getSpawnPosition();
                        $isNearTeamSpawn = Utils::getXZDistance($pos, $teamSpawn) <= 7 && $pos->getY() >= $teamSpawn->getY() - 3;
                        $isNearTeamPoint = Utils::getXZDistance($pos, $t->getPoint()) <= 4;

                        if ($isNearTeamSpawn || $isNearTeamPoint) {
                            $player->sendMessage(TextFormat::RED . "You can't place blocks there!");
                            $event->cancel();
                            return;
                        }
                    }
                }
            } else {
                $event->cancel();
            }
        } else {
            $event->cancel();
        }
    }

    /**
     * @return BridgeArena
     */
    public function getArena(): Arena
    {
        /** @var BridgeArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();

        if (!in_array(
            $block->getStateId(),
            array_map(fn(StainedHardenedClay $clay) => $clay->getStateId(), $this->getArena()->getInteractableBlocks())
        )) {
            if ($this->getArena()->getGameSettings()->hasNoProtection()) {
                $event->setDrops([]);
            } else {
                $event->cancel();
            }
        }
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $event->cancel();
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    public function onEntityShootBow(EntityShootBowEvent $event): void
    {
        /** @var Player $player */
        $player = $event->getEntity();

        if (!$this->getArena()->canScoreGoal()) {
            $event->cancel();
        } elseif (($xpManager = $player->getXpManager())->getXpLevel() > 0) {
            $player->sendMessage(TextFormat::RED . "You don't have any arrows right now! Arrows regenerate every 3.5s.");
            $event->cancel();
        } elseif (!$this->getArena()->getGameSettings()->hasNoBowCooldown()) {
            $xpManager->setXpLevel(4);
            $xpManager->setXpProgress(0.5);
        }
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $event->cancel();
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        if ($item->equals(Items::getPreferencesSelector())) {
            LayoutEditor::sendPresetMenu($player);
        }

        return parent::onItemInteract($player, $item);
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        if ($event->getItem()->getTypeId() === ItemTypeIds::GOLDEN_APPLE) {
            $event->getPlayer()->setHealth($event->getPlayer()->getMaxHealth());
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $team = $this->getArena()->getTeam($player);
            $cause = $event->getCause();
            $ess = $this->getArena()->getPlugin()->getEssentials();

            switch ($cause) {
                case EntityDamageEvent::CAUSE_FALL:
                    $event->cancel();
                    break;
                case EntityDamageEvent::CAUSE_VOID:
                    $event->cancel();

                    if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                        $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause(), true)));
                        $this->getArena()->addKill($damager, $player);

                        $effects = $damager->getEffects();
                        $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
                        $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));
                    } else {
                        $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())));
                    }

                    $this->onPlayerDeath($player, $team);
                    break;
                default:
                    if ($event->getFinalDamage() >= $event->getEntity()->getHealth()) {
                        $event->cancel();

                        switch ($cause) {
                            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                            case EntityDamageEvent::CAUSE_PROJECTILE:
                                if ($event instanceof EntityDamageByEntityEvent) {
                                    $damager = $event->getDamager();
                                    if ($damager instanceof Player) {
                                        $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())));
                                        $this->getArena()->addKill($damager, $player);

                                        $effects = $damager->getEffects();
                                        $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
                                        $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));

                                        $this->onPlayerDeath($player, $team);
                                    }
                                }
                                break;
                            case EntityDamageEvent::CAUSE_FALL:
                            case EntityDamageEvent::CAUSE_VOID:
                            case EntityDamageEvent::CAUSE_LAVA:
                                if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                                    $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause(), true)));
                                    $this->getArena()->addKill($damager, $player);

                                    $effects = $damager->getEffects();
                                    $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
                                    $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));
                                } else {
                                    $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())));
                                }

                                $this->onPlayerDeath($player, $team);
                                break;
                            default:
                                $this->getArena()->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($player), $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())));
                                $this->onPlayerDeath($player, $team);
                                break;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $player = $event->getEntity();

        if ($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK && $this->getArena()->getGameSettings()->hasInstantSword() && $player instanceof Player && $damager instanceof Player && $damager->getInventory()->getItemInHand() instanceof Sword) {
            $team = $this->getArena()->getTeam($player);

            $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$team->getPlayerName($player), $this->getArena()->getTeam($damager)->getPlayerName($damager)], $this->getArena()->getPlugin()->getRandomKillMessage($event->getCause())));
            $this->getArena()->addKill($damager, $player);

            $effects = $damager->getEffects();
            $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
            $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));

            $this->onPlayerDeath($player, $team);

            $event->cancel();
        }
    }

    public function onPlayerDeath(Player $player, BridgeTeam $team): void
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();
        $plugin->getEssentials()->getCombatLogger()->wipeLog($player);

        $team->respawnPlayer($player);

        $statsData = $arena->getStatsData();
        $statsData->addValue($player, StatsData::TB_DEATHS);
        $statsData->addValue($player, StatsData::TB_MODE_DEATHS);
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $event->cancel();
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $event->getEntity()->flagForDespawn();
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
}