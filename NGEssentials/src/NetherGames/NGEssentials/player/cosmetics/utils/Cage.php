<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils;

use libasyncio\blocks\Selection;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\world\World;

class Cage
{
    private ?CageEntity $entity = null;
    private ?Selection $despawnSelection = null;

    public function __construct(private Selection $selection, private Location $center, ?string $entityId = null, ?string $spawnAnimation = null)
    {
        if ($entityId !== null) {
            $this->entity = new CageEntity(
                Location::fromObject($center->add(0.5, 0, 0.5), $center->getWorld(), $center->getYaw(), $center->getPitch()),
                $entityId,
                $spawnAnimation
            );
        }
    }

    public function spawnCage(Selection $selection): ?CageEntity
    {
        $this->despawnSelection = new Selection();

        foreach ($this->selection->getBlocks() as $hash => $blockId) {
            World::getBlockXYZ($hash, $x, $y, $z);

            $x += $this->center->getX();
            $y += $this->center->getY();
            $z += $this->center->getZ();
            $currentBlock = $this->center->getWorld()->getBlockAt($x, $y, $z);

            $selection->addRaw($newHash = World::blockHash($x, $y, $z), $blockId);
            $this->despawnSelection->addRaw($newHash, $currentBlock->getStateId());
        }

        return $this->entity;
    }

    public function getSpawnAnimation(): ?ClientboundPacket
    {
        return $this->entity?->getSpawnAnimation();
    }

    public function despawnCage(Selection $selection): ?CageEntity
    {
        if ($this->despawnSelection !== null) {
            $selection->addSelection($this->despawnSelection);

            return $this->entity;
        }

        return null;
    }
}