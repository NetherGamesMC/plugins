<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\Tag;

abstract class ItemComponent
{
    abstract public function getName(): string;

    abstract public function getValue(int $protocolId): Tag;

    public function isProperty(): bool
    {
        return false;
    }
}