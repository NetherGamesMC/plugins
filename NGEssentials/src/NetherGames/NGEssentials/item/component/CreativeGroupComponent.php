<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use NetherGames\NGEssentials\item\CreativeInventoryInfo;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;

final class CreativeGroupComponent extends PropertyItemComponent
{

    public function __construct(private readonly CreativeInventoryInfo $creativeInfo)
    {
    }

    public function getName(): string
    {
        return "creative_group";
    }

    public function getValue(int $protocolId): Tag
    {
        return new StringTag($this->creativeInfo->getGroup());
    }
}