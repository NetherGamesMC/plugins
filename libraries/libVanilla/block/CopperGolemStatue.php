<?php
declare(strict_types=1);

namespace libVanilla\block;

use libVanilla\block\tile\TileCopperGolemStatue;
use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeInfo as BTI;
use pocketmine\block\Opaque;
use pocketmine\block\utils\HorizontalFacing;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

/**
 * Copper Golem Statue block.
 * Each oxidation/waxed variant is registered as a separate block with its own type ID.
 * Only the facing direction is stored as block state.
 * Pose (0-3) is stored in the tile entity.
 */
class CopperGolemStatue extends Opaque implements HorizontalFacing
{
    use HorizontalFacingTrait;

    public const MAX_POSE = 3;

    public function __construct(BID $id, string $name, BTI $info)
    {
        parent::__construct($id, $name, $info);
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        if ($player !== null) {
            $this->setFacing(Facing::opposite($player->getHorizontalFacing()));
        }
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool
    {
        if ($player === null || !$item->isNull()) {
            return false;
        }

        $tile = $this->position->getWorld()->getTile($this->position);
        if ($tile instanceof TileCopperGolemStatue) {
            $nextPose = ($tile->getPose() + 1) % (self::MAX_POSE + 1);
            $tile->setPose($nextPose);
            $tile->clearSpawnCompoundCache();
            // Re-set the block to trigger chunk dirty + tile spawn data re-send
            $this->position->getWorld()->setBlock($this->position, $this);
            return true;
        }
        return false;
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty - prevent unmapped blockstate errors
    }
}
