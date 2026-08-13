<?php
declare(strict_types=1);

namespace libVanilla\block\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\TypeConverter;

/**
 * Tile entity for the Decorated Pot block.
 * The Bedrock client requires this tile entity to render the block properly in chunk data.
 */
class TileDecoratedPot extends Spawnable
{

    private const TAG_SHERDS = "sherds";

    /** @var string[] */
    private array $sherds = [
        "minecraft:brick",
        "minecraft:brick",
        "minecraft:brick",
        "minecraft:brick",
    ];

    public function readSaveData(CompoundTag $nbt): void
    {
        $sherdsTag = $nbt->getListTag(self::TAG_SHERDS);
        if ($sherdsTag !== null) {
            $this->sherds = [];
            foreach ($sherdsTag as $tag) {
                if ($tag instanceof StringTag) {
                    $this->sherds[] = $tag->getValue();
                }
            }
            // Ensure we always have exactly 4 sherds
            while (count($this->sherds) < 4) {
                $this->sherds[] = "minecraft:brick";
            }
        }
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $list = new ListTag();
        foreach ($this->sherds as $sherd) {
            $list->push(new StringTag($sherd));
        }
        $nbt->setTag(self::TAG_SHERDS, $list);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, TypeConverter $typeConverter): void
    {
        $list = new ListTag();
        foreach ($this->sherds as $sherd) {
            $list->push(new StringTag($sherd));
        }
        $nbt->setTag(self::TAG_SHERDS, $list);
    }
}
