<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Flowable;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;

class CherrySapling extends Flowable
{
    use StaticSupportTrait;

    protected bool $ready = false;

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->bool($this->ready);
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    /** @return $this */
    public function setReady(bool $ready): self
    {
        $this->ready = $ready;
        return $this;
    }

    /** @phpstan-ignore method.unused */
    private function canBeSupportedAt(Block $block): bool
    {
        $supportBlock = $block->getSide(Facing::DOWN);
        return $supportBlock->hasTypeTag(BlockTypeTags::DIRT) || $supportBlock->hasTypeTag(BlockTypeTags::MUD);
    }
}
