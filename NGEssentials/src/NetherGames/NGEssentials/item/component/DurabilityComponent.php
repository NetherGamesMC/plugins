<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class DurabilityComponent extends ItemComponent
{

    public function __construct(private readonly int $maxDurability)
    {
    }

    public function getName(): string
    {
        return "minecraft:durability";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setInt("max_durability", $this->maxDurability);
    }
}