<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use NetherGames\NGEssentials\item\CreativeInventoryInfo;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Tag;

final class CreativeCategoryComponent extends PropertyItemComponent
{

    public function __construct(private readonly CreativeInventoryInfo $creativeInfo)
    {
    }

    public function getName(): string
    {
        return "creative_category";
    }

    public function getValue(int $protocolId): Tag
    {
        return new IntTag($this->creativeInfo->getNumericCategory());
    }
}