<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class ArmorComponent extends ItemComponent
{

    public const TEXTURE_TYPE_CHAIN = "chain";
    public const TEXTURE_TYPE_DIAMOND = "diamond";
    public const TEXTURE_TYPE_ELYTRA = "elytra";
    public const TEXTURE_TYPE_GOLD = "gold";
    public const TEXTURE_TYPE_IRON = "iron";
    public const TEXTURE_TYPE_LEATHER = "leather";
    public const TEXTURE_TYPE_NETHERITE = "netherite";
    public const TEXTURE_TYPE_NONE = "none";
    public const TEXTURE_TYPE_TURTLE = "turtle";

    public function __construct(private readonly int $protection, private readonly string $textureType = self::TEXTURE_TYPE_NONE)
    {
    }

    public function getName(): string
    {
        return "minecraft:armor";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setInt("protection", $this->protection)
            ->setString("texture_type", $this->textureType);
    }
}