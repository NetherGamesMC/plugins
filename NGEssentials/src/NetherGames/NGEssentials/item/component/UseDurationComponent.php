<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Tag;

final class UseDurationComponent extends PropertyItemComponent
{

    public function __construct(private readonly int $duration)
    {
    }

    public function getName(): string
    {
        return "use_duration";
    }

    public function getValue(int $protocolId): Tag
    {
        return new IntTag($this->duration);
    }
}