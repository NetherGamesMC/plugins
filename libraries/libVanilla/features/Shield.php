<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\features;

use Closure;
use libVanilla\item\Shield as ShieldItem;
use libVanilla\network\PacketHandler;
use libVanilla\network\PacketProcessor;
use libVanilla\sound\ShieldBlockSound;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;

// https://minecraft.fandom.com/wiki/Shield
class Shield extends Feature implements PacketHandler
{
    private const BLOCKING_DELAY = 5;

    /** @var array<int, int> */
    private array $blockingStart = [];
    /** @var array<int, TaskHandler<ClosureTask>> */
    private array $reBlockTasks = [];
    private TaskScheduler $scheduler;

    protected function setup(PluginBase $plugin): void
    {
        $this->scheduler = $plugin->getScheduler();
        $plugin->getServer()->getPluginManager()->registerEvent(EntityDamageEvent::class, Closure::fromCallable([$this, "onDamage"]), EventPriority::HIGH, $plugin);
        $plugin->getServer()->getPluginManager()->registerEvent(PlayerToggleSneakEvent::class, Closure::fromCallable([$this, "onSneak"]), EventPriority::HIGH, $plugin);
        $plugin->getServer()->getPluginManager()->registerEvent(PlayerQuitEvent::class, Closure::fromCallable([$this, "onQuit"]), EventPriority::NORMAL, $plugin);

        PacketProcessor::getInstance()->registerHandler($this, $plugin);
    }

    public function handleAnimatePacket(NetworkSession $origin, AnimatePacket $packet): bool
    {
        $player = $origin->getPlayer();
        if ($packet->action !== AnimatePacket::ACTION_SWING_ARM || $player === null) {
            return false;
        }

        if (self::getShield($player) === null || !$this->isBlocking($player, true)) {
            return false;
        }

        $this->setBlocking($player, false);
        $this->reBlockTasks[$player->getId()] = $this->scheduler->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
            if (!$player->isOnline() || self::getShield($player) === null) {
                return;
            }
            $this->setBlocking($player, true);
        }), self::BLOCKING_DELAY);

        return false; // server still needs to handle this, we're just adding extra actions
    }

    private function resetBlockingStatus(Player $player): void
    {
        $k = $player->getId();
        ($this->reBlockTasks[$k] ?? null)?->cancel();
        unset($this->blockingStart[$k], $this->reBlockTasks[$k]);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $this->resetBlockingStatus($event->getPlayer());
    }

    public function isBlocking(Player $player, bool $ignoreDelay = false): bool
    {
        return isset($this->blockingStart[$player->getId()]) && ($ignoreDelay || $this->blockingStart[$player->getId()] <= $player->getServer()->getTick());
    }

    /**
     * @return array{Inventory, ShieldItem, int}|null
     */
    private static function getShield(Player $player): ?array
    {
        if (($item = ($inv = $player->getInventory())->getItemInHand()) instanceof ShieldItem) {
            return [$inv, $item, $inv->getHeldItemIndex()];
        }
        if (($item = ($inv = $player->getOffHandInventory())->getItem(0)) instanceof ShieldItem) {
            return [$inv, $item, 0];
        }
        return null;
    }

    private static function knockBack(Living $target, Entity $source, float $force): void
    {
        $deltaX = $target->getPosition()->x - $source->getPosition()->x;
        $deltaZ = $target->getPosition()->z - $source->getPosition()->z;
        $target->knockBack($deltaX, $deltaZ, $force);
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof Player || !$player->isSneaking()) {
            return;
        }
        if (!$this->isBlocking($player)) {
            return;
        }

        if (!$event->canBeReducedByArmor()) {
            return;
        }

        $unpack = self::getShield($player);
        if ($unpack === null) {
            return;
        }
        /** @var Inventory $shieldInv */
        /** @var ShieldItem $shieldItem */
        /** @var int $shieldSlot */
        [$shieldInv, $shieldItem, $shieldSlot] = $unpack;

        if ($event instanceof EntityDamageByChildEntityEvent) {
            $srcEntity = $event->getChild();
            if ($srcEntity === null) {
                return;
            }
            $srcPos = $srcEntity->getPosition();
        } elseif ($event instanceof EntityDamageByEntityEvent) {
            $srcEntity = $event->getDamager();
            if ($srcEntity === null) {
                return;
            }
            $srcPos = $srcEntity->getPosition();
        } else {
            return;
        }

        // ignore if from behind... unshielded
        if ($player->getDirectionVector()->dot($player->getPosition()->subtractVector($srcPos)) > 0) {
            return;
        }

        $dmg = (int)ceil($event->getBaseDamage());
        if ($player->isSurvival() && $dmg >= 3) {
            $shieldItem->applyDamage($dmg);
            $shieldInv->setItem($shieldSlot, $shieldItem);
        }

        $cause = $event->getCause();
        if ($cause === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION || $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION) {
            // does not work without a delay... not even 0-tick, not sure why.
            $this->scheduler->scheduleDelayedTask(new ClosureTask(
                fn() => self::knockBack($player, $srcEntity, $event->getKnockBack() / 5)
            ), 1);
        }

        if ($event instanceof EntityDamageByChildEntityEvent) {
            // todo: make it NOT despawn, and set motion to reverse
        } elseif ($event instanceof EntityDamageByEntityEvent) {
            $dmg = $event->getDamager();
            if ($dmg instanceof Living) {
                self::knockBack($dmg, $player, $event->getKnockBack()); // perhaps divide it by 5 as well? a little too powerful imo, but that's vanilla
            }
        }

        $player->broadcastSound(new ShieldBlockSound());

        $event->cancel();
    }

    public function onSneak(PlayerToggleSneakEvent $event): void
    {
        $player = $event->getPlayer();
        $this->setBlocking($player, $event->isSneaking() && self::getShield($player) !== null);
    }

    public function setBlocking(Player $player, bool $blocking): void
    {
        // figure out what this is for: $p->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::TRANSITION_BLOCKING, $blocking);
        $player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::BLOCKING, $blocking);
        if (!$blocking) {
            $this->resetBlockingStatus($player);
        } else {
            $this->blockingStart[$player->getId()] = $player->getServer()->getTick() + self::BLOCKING_DELAY;
        }
    }
}