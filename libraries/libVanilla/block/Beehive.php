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
use pocketmine\item\ItemBlock;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class Beehive extends Opaque implements HorizontalFacing
{
    use HorizontalFacingTrait;

    public const MIN_HONEY_LEVEL = 0;
    public const MAX_HONEY_LEVEL = 5;

    protected int $facing = Facing::NORTH;
    protected int $honeyLevel = 0;

    public function __construct(BID $id, string $name, BTI $info)
    {
        parent::__construct($id, $name, $info);
    }

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->horizontalFacing($this->facing);
        $w->boundedIntAuto(self::MIN_HONEY_LEVEL, self::MAX_HONEY_LEVEL, $this->honeyLevel);
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty - prevent unmapped blockstate errors for item form
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool
    {
        if ($player !== null) {
            $this->setFacing(Facing::opposite($player->getHorizontalFacing()));
        }
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function getHoneyLevel(): int
    {
        return $this->honeyLevel;
    }

    /** @return $this */
    public function setHoneyLevel(int $level): self
    {
        if ($level < self::MIN_HONEY_LEVEL || $level > self::MAX_HONEY_LEVEL) {
            throw new \InvalidArgumentException("Honey level must be between " . self::MIN_HONEY_LEVEL . " and " . self::MAX_HONEY_LEVEL);
        }
        $this->honeyLevel = $level;
        return $this;
    }

    public function setFacing(int $facing): self
    {
        $this->facing = $facing;
        return $this;
    }

    public function getFacing(): int
    {
        return $this->facing;
    }

    public function getDrops(Item $item): array
    {
        return [new ItemBlock($this)];
    }

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool
    {
        // interaction logic not implemented; only used as cosmetical block for now
        return false;
    }
}
