<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Flowable;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class MossCarpet extends Flowable
{
    use StaticSupportTrait;

    public function isSolid(): bool
    {
        return true;
    }

    protected function recalculateCollisionBoxes(): array
    {
        return [AxisAlignedBB::one()->trim(Facing::UP, 15 / 16)];
    }

    /** @phpstan-ignore method.unused */
    private function canBeSupportedAt(Block $block): bool
    {
        return $block->getSide(Facing::DOWN)->getTypeId() !== BlockTypeIds::AIR;
    }
}
