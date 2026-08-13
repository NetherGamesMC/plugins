<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Tag;

final class CanDestroyInCreativeComponent extends PropertyItemComponent
{

    public function __construct(private readonly bool $canDestroyInCreative = true)
    {
    }

    public function getName(): string
    {
        return "can_destroy_in_creative";
    }

    public function getValue(int $protocolId): Tag
    {
        return new ByteTag($this->canDestroyInCreative ? 1 : 0);
    }
}
