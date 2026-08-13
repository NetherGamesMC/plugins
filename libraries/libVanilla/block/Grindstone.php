<?php
declare(strict_types=1);

namespace libVanilla\block;

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
 * Grindstone block - attachment types:
 * 0 = standing (floor), 1 = hanging (ceiling), 2 = side (wall)
 */
class Grindstone extends Opaque implements HorizontalFacing
{
    use HorizontalFacingTrait {
        describeBlockOnlyState as describeHorizontalFacing;
    }

    public const ATTACHMENT_STANDING = 0;
    public const ATTACHMENT_HANGING = 1;
    public const ATTACHMENT_SIDE = 2;

    protected int $attachment = self::ATTACHMENT_STANDING;

    public function __construct(BID $id, string $name, BTI $info)
    {
        parent::__construct($id, $name, $info);
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        if ($face === Facing::UP) {
            $this->setAttachment(self::ATTACHMENT_STANDING);
            if ($player !== null) {
                $this->setFacing(Facing::opposite($player->getHorizontalFacing()));
            }
        } elseif ($face === Facing::DOWN) {
            $this->setAttachment(self::ATTACHMENT_HANGING);
            if ($player !== null) {
                $this->setFacing(Facing::opposite($player->getHorizontalFacing()));
            }
        } else {
            $this->setAttachment(self::ATTACHMENT_SIDE);
            $this->setFacing($face);
        }
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $this->describeHorizontalFacing($w);
        $w->boundedIntAuto(0, 2, $this->attachment);
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty - prevent unmapped blockstate errors
    }

    public function getAttachment(): int
    {
        return $this->attachment;
    }

    /** @return $this */
    public function setAttachment(int $attachment): self
    {
        if ($attachment < 0 || $attachment > 2) {
            throw new \InvalidArgumentException("Attachment must be between 0 and 2");
        }
        $this->attachment = $attachment;
        return $this;
    }
}
