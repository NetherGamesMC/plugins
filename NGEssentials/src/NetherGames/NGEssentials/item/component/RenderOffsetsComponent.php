<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\Tag;

final class RenderOffsetsComponent extends ItemComponent
{

    public function __construct(private readonly int $textureWidth, private readonly int $textureHeight, private readonly bool $handEquipped = false)
    {
    }

    public function getName(): string
    {
        return "minecraft:render_offsets";
    }

    public function getValue(int $protocolId): Tag
    {
        $horizontal = ($this->handEquipped ? 0.075 : 0.1) / ($this->textureWidth / 16);
        $vertical = ($this->handEquipped ? 0.125 : 0.1) / ($this->textureHeight / 16);

        $scale = new ListTag([
            new FloatTag($horizontal),
            new FloatTag($vertical),
            new FloatTag($horizontal)
        ]);

        $perspectives = CompoundTag::create()
            ->setTag("first_person", CompoundTag::create()
                ->setTag("scale", $scale)
            )->setTag("third_person", CompoundTag::create()
                ->setTag("scale", $scale)
            );

        return CompoundTag::create()
            ->setTag('main_hand', $perspectives)
            ->setTag('off_hand', $perspectives);
    }
}