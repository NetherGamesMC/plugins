<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class IconComponent extends PropertyItemComponent
{

    public function __construct(private readonly string $texture)
    {
    }

    public function getName(): string
    {
        return "minecraft:icon";
    }

    public function getValue(int $protocolId): Tag
    {
        if ($protocolId >= ProtocolInfo::PROTOCOL_1_20_60) {
            return CompoundTag::create()
                ->setTag("textures", CompoundTag::create()->setString("default", $this->texture));
        }

        return CompoundTag::create()
            ->setString("texture", $this->texture);
    }
}