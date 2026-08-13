<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class DisplayNameComponent extends ItemComponent
{

    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return "minecraft:display_name";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setString("value", $this->name);
    }
}