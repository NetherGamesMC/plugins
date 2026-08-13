<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class BlockPlacerComponent extends ItemComponent
{

    public function __construct(private readonly string $blockIdentifier, private readonly bool $useBlockDescription = false)
    {
    }

    public function getName(): string
    {
        return "minecraft:block_placer";
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()
            ->setString("block", $this->blockIdentifier)
            ->setByte("use_block_description", $this->useBlockDescription ? 1 : 0);
    }
}
