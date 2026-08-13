<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class FuelComponent extends ItemComponent
{

    public function __construct(private readonly float $duration)
    {
    }

    public function getName(): string
    {
        return "minecraft:fuel";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setFloat("duration", $this->duration);
    }
}