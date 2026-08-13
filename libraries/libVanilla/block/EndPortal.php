<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Transparent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class EndPortal extends Transparent
{
    /**
     * @return AxisAlignedBB[]
     */
    protected function recalculateCollisionBoxes(): array
    {
        return [AxisAlignedBB::one()->trim(Facing::UP, 1 / 4)];
    }
}
