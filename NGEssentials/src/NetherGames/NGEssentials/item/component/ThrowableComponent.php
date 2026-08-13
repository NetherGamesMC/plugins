<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class ThrowableComponent extends ItemComponent
{

    public function __construct(private readonly bool $doSwingAnimation)
    {
    }

    public function getName(): string
    {
        return "minecraft:throwable";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setByte("do_swing_animation", $this->doSwingAnimation ? 1 : 0);
    }
}