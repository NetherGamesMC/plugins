<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Tag;

final class HandEquippedComponent extends PropertyItemComponent
{

    public function __construct(private readonly bool $handEquipped = true)
    {
    }

    public function getName(): string
    {
        return "hand_equipped";
    }

    public function getValue(int $protocolId): Tag
    {
        return new ByteTag($this->handEquipped ? 1 : 0);
    }
}