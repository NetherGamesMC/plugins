<?php

declare(strict_types=1);

namespace libVanilla\block;

use libVanilla\block\behaviour\HopperBehaviourManager;
use pocketmine\block\Block;
use pocketmine\block\Hopper as VanillaHopper;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Hopper as HopperTile;
use pocketmine\block\tile\Tile;
use pocketmine\math\Facing;

class Hopper extends VanillaHopper
{
    private int $transferCap = 0;

    public function readStateFromWorld(): Block
    {
        $this->updateHopperTickers();

        return parent::readStateFromWorld();
    }

    protected function updateHopperTickers(): void
    {
        if ($this->canRescheduleTransferCooldown()) {
            $this->rescheduleTransferCooldown();
        }
    }

    protected function canRescheduleTransferCooldown(): bool
    {
        return ($this->getContainerAbove() ?? $this->getContainerFacing($this->getFacing())) !== null;
    }

    public function getContainerAbove(): ?Container
    {
        $above = $this->position->getWorld()->getTileAt($this->position->x, $this->position->y + 1, $this->position->z);
        return $above instanceof Container ? $above : null;
    }

    public function getContainerFacing(int $face): ?Container
    {
        $facing_pos = $this->position->getSide($face);
        $facing = $this->position->getWorld()->getTileAt($facing_pos->x, $facing_pos->y, $facing_pos->z);
        return $facing instanceof Container ? $facing : null;
    }

    protected function rescheduleTransferCooldown(): void
    {
        $config = HopperConfig::getInstance();
        $scheduler = $config->getBlockScheduler();

        $requestedDelay = $config->getTransferTickRate();
        $actualDelay = $scheduler->scheduleDelayedBlockUpdate($this->position->getWorld(), $this->position, $config->getTransferTickRate());

        assert($actualDelay >= $requestedDelay);
        $this->transferCap = $config->getTransferPerTick() * (1 + ($actualDelay - $requestedDelay));
    }

    public function onNearbyBlockChange(): void
    {
        parent::onNearbyBlockChange();
        $this->updateHopperTickers();
    }

    public function onScheduledUpdate(): void
    {
        if (($hopperInventory = $this->getInventory()) === null) {
            return;
        }
        $face = $this->getFacing();
        $facing = $this->getContainerFacing($face);

        if ($this->transferCap === 0) {
            $this->transferCap = HopperConfig::getInstance()->getTransferPerTick();
        }

        if ($facing !== null) {
            assert($facing instanceof Tile);
            if ($face !== Facing::DOWN) {
                HopperBehaviourManager::getFromTile($facing)->side($hopperInventory, $facing->getInventory(), $this->transferCap);
            } else {
                HopperBehaviourManager::getFromTile($facing)->below($hopperInventory, $facing->getInventory(), $this->transferCap);
            }
        }

        if (($above = $this->getContainerAbove()) !== null) {
            assert($above instanceof Tile);
            HopperBehaviourManager::getFromTile($above)->above($hopperInventory, $above->getInventory(), $this->transferCap);
        }

        $this->updateHopperTickers();
    }

    public function getInventory(): ?HopperInventory
    {
        $tile = $this->position->getWorld()->getTileAt($this->position->x, $this->position->y, $this->position->z);
        return $tile instanceof HopperTile ? $tile->getInventory() : null;
    }
}