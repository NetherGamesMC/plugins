<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class KnockbackResistanceComponent extends ItemComponent
{

    public function __construct(private readonly float $protection)
    {
    }

    public function getName(): string
    {
        return "minecraft:knockback_resistance";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setFloat("protection", $this->protection);
    }
}