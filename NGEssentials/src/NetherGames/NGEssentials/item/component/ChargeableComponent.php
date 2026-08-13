<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class ChargeableComponent extends ItemComponent
{

    public function __construct(private readonly float $movementModifier)
    {
    }

    public function getName(): string
    {
        return "minecraft:chargeable";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setFloat("movement_modifier", $this->movementModifier);
    }
}