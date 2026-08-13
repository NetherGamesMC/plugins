<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Tag;

final class MaxStackSizeComponent extends PropertyItemComponent
{

    public function __construct(private readonly int $maxStackSize)
    {
    }

    public function getName(): string
    {
        return "max_stack_size";
    }

    public function getValue(int $protocolId): Tag
    {
        return new IntTag($this->maxStackSize);
    }
}