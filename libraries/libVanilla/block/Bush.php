<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeTags;
use pocketmine\block\Flowable;
use pocketmine\block\utils\StaticSupportTrait;
use pocketmine\math\Facing;

class Bush extends Flowable
{
    use StaticSupportTrait;

    /** @phpstan-ignore method.unused */
    private function canBeSupportedAt(Block $block): bool
    {
        $supportBlock = $block->getSide(Facing::DOWN);
        return $supportBlock->hasTypeTag(BlockTypeTags::DIRT) || $supportBlock->hasTypeTag(BlockTypeTags::MUD);
    }
}
