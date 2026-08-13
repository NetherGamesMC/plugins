<?php
declare(strict_types=1);

namespace libVanilla\block\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;

/**
 * Tile entity for the Copper Golem Statue block.
 * The Bedrock client requires this tile entity to render the block properly in chunk data.
 * Stores the golem's pose.
 */
class TileCopperGolemStatue extends Spawnable
{

    private const TAG_POSE = "Pose";

    private int $pose = 0;

    public function getPose(): int
    {
        return $this->pose;
    }

    /** @return $this */
    public function setPose(int $pose): self
    {
        $this->pose = $pose;
        return $this;
    }

    public function readSaveData(CompoundTag $nbt): void
    {
        $this->pose = $nbt->getInt(self::TAG_POSE, 0);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setInt(self::TAG_POSE, $this->pose);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, TypeConverter $typeConverter): void
    {
        $nbt->setInt(self::TAG_POSE, $this->pose);
    }
}
