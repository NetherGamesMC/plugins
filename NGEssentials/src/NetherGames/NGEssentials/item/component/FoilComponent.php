<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Tag;

final class FoilComponent extends PropertyItemComponent
{

    public function __construct(private readonly bool $foil = true)
    {
    }

    public function getName(): string
    {
        return "foil";
    }

    public function getValue(int $protocolId): Tag
    {
        return new ByteTag($this->foil ? 1 : 0);
    }
}