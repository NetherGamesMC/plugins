<?php
declare(strict_types=1);

namespace libVanilla\block;

use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeInfo as BTI;
use pocketmine\block\Opaque;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Composter extends Opaque
{
    protected int $fillLevel = 0;

    public function __construct(BID $id, string $name, BTI $info)
    {
        parent::__construct($id, $name, $info);
    }

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->boundedIntAuto(0, 8, $this->fillLevel);
    }

    public function describeBlockItemState(RuntimeDataDescriber $w): void
    {
        // intentionally empty - prevent unmapped blockstate errors
    }

    public function getFillLevel(): int
    {
        return $this->fillLevel;
    }

    /** @return $this */
    public function setFillLevel(int $level): self
    {
        if ($level < 0 || $level > 8) {
            throw new \InvalidArgumentException("Fill level must be between 0 and 8");
        }
        $this->fillLevel = $level;
        return $this;
    }
}
