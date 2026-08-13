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
 * @author Drew, Driesboy, sylvrs, CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\item;

use libVanilla\entity\object\FishingHook;
use libVanilla\event\fishing\FishingRodCastEvent;
use libVanilla\event\fishing\FishingRodRetractionEvent;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\item\Durable;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;

class FishingRod extends Durable
{
    public const HOOK_ENTITY_TAG = "runtime-id";
    private bool $combatRod = false;

    public function setCombatRod(bool $combatRod): void
    {
        $this->combatRod = $combatRod;
    }

    public function isCombatRod(): bool
    {
        return $this->combatRod;
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }

    public function getMaxDurability(): int
    {
        return 384;
    }

    public function getFuelTime(): int
    {
        return 300;
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult
    {
        if (!$this->combatRod) {
            return (!$this->hasCasted($player) ? $this->cast($player) : $this->reel($player)) ? ItemUseResult::SUCCESS() : ItemUseResult::FAIL();
        }

        if ($this->hasCasted($player)) {
            $hook = $this->getFishingHook($player);

            if ($hook !== null && $hook->getTargetEntity() !== null) {
                if (!$hook->isFlaggedForDespawn()) {
                    $hook->flagForDespawn();
                }

                $result = $this->cast($player);
            } else {
                $result = $this->reel($player);
            }
        } else {
            $result = $this->cast($player);
        }

        return $result ? ItemUseResult::SUCCESS() : ItemUseResult::FAIL();
    }

    public function hasCasted(Player $player): bool
    {
        return $this->getFishingHook($player) !== null;
    }

    public function getRuntimeHookId(): int
    {
        return $this->getNamedTag()->getInt(self::HOOK_ENTITY_TAG, -1);
    }

    /**
     * @param Player $player
     * @return bool - Returns false if the event was cancelled
     */
    public function cast(Player $player): bool
    {
        $location = $player->getLocation();
        $hook = new FishingHook(
            Location::fromObject(
                $location->add(0, $player->getEyeHeight() * 0.75, 0),
                $location->getWorld(),
                $location->getYaw(),
                $location->getPitch()
            ),
            $player,
            combatHook: $this->combatRod
        );

        $event = new FishingRodCastEvent($player, $this);
        $event->call();
        if ($event->isCancelled()) {
            return false;
        }

        $hook->setMotion($player->getDirectionVector()->multiply($this->combatRod ? 1.8 : 1.3));
        $hook->spawnToAll();

        $player->getWorld()->addSound($location, new ThrowSound());
        $this->setRuntimeHookId($hook->getId());
        $player->getInventory()->setItemInHand($this);
        return true;
    }

    public function setRuntimeHookId(int $id): void
    {
        $this->getNamedTag()->setInt(self::HOOK_ENTITY_TAG, $id);
    }

    /**
     * @param Player $player
     * @return bool - Returns false if the event was cancelled
     */
    public function reel(Player $player): bool
    {
        $hook = $this->getFishingHook($player);
        if ($hook instanceof FishingHook) {
            $event = new FishingRodRetractionEvent($player, $this);
            $event->call();
            if ($event->isCancelled()) {
                return false;
            }

            if (!$hook->isFlaggedForDespawn()) {
                $hook->flagForDespawn();
            }

            if (!$this->combatRod) {
                $attachedTo = $hook->getTargetEntity();
                if ($attachedTo instanceof Living && $attachedTo !== $player) {
                    $motion = $attachedTo->getLocation()->subtractVector($player->getEyePos())->normalize();
                    $motion->x *= -0.5;
                    $motion->y *= -1.5;
                    $motion->z *= -0.5;

                    $limit = 0.4;

                    if (abs($motion->x) > $limit) {
                        $motion->x = $limit * ($motion->x < 0 ? -1 : 1);
                    }
                    if (abs($motion->z) > $limit) {
                        $motion->z = $limit * ($motion->z < 0 ? -1 : 1);
                    }

                    $attachedTo->setMotion($motion);
                }
            }
        }

        $this->setRuntimeHookId(-1);
        $this->applyDamage(1);
        $player->getInventory()->setItemInHand($this);

        return true;
    }

    public function getFishingHook(Player $player): ?FishingHook
    {
        $hook = $player->getWorld()->getEntity($this->getRuntimeHookId());
        return $hook instanceof FishingHook ? $hook : null;
    }

}