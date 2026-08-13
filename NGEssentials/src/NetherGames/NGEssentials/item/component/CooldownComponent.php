<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class CooldownComponent extends ItemComponent
{

    public function __construct(private readonly string $category, private readonly float $duration)
    {
    }

    public function getName(): string
    {
        return "minecraft:cooldown";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setString("category", $this->category)
            ->setFloat("duration", $this->duration);
    }
}