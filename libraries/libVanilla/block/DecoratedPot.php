<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeInfo as BTI;
use pocketmine\block\Opaque;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;

class DecoratedPot extends Opaque implements HorizontalFacing
{
    use HorizontalFacingTrait;

    public function __construct(BID $id, string $name, BTI $info)
    {
        parent::__construct($id, $name, $info);
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty - prevent unmapped blockstate errors
    }
}
