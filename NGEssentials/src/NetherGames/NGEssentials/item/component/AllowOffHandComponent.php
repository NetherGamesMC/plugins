<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Tag;

final class AllowOffHandComponent extends PropertyItemComponent
{

    public function __construct(private readonly bool $offHand = true)
    {
    }

    public function getName(): string
    {
        return "allow_off_hand";
    }

    public function getValue(int $protocolId): Tag
    {
        return new ByteTag($this->offHand ? 1 : 0);
    }
}