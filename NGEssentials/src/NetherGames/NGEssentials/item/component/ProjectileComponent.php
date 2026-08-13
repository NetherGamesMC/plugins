<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class ProjectileComponent extends ItemComponent
{

    public function __construct(private readonly string $projectileEntity)
    {
    }

    public function getName(): string
    {
        return "minecraft:projectile";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setString("projectile_entity", $this->projectileEntity);
    }
}