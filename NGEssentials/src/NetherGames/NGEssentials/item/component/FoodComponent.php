<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class FoodComponent extends ItemComponent
{

    public function __construct(private readonly bool $canAlwaysEat = false)
    {
    }

    public function getName(): string
    {
        return "minecraft:food";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setByte("can_always_eat", $this->canAlwaysEat ? 1 : 0);
    }
}