<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function array_map;
use function implode;

final class DiggerComponent extends ItemComponent
{
    private ListTag $destroySpeeds;

    public function __construct()
    {
        $this->destroySpeeds = new ListTag();
    }

    public function getValue(int $protocolId): Tag
    {
        return CompoundTag::create()->setTag('destroy_speeds', $this->destroySpeeds);
    }

    public function withBlocks(int $speed, Block ...$blocks): DiggerComponent
    {
        foreach ($blocks as $block) {
            $this->destroySpeeds->push(CompoundTag::create()
                ->setTag('block', CompoundTag::create()
                    ->setString('name', GlobalBlockStateHandlers::getSerializer()->serialize($block->getStateId())->getName()))
                ->setInt('speed', $speed)
            );
        }

        return $this;
    }

    public function getName(): string
    {
        return "minecraft:digger";
    }

    public function withTags(int $speed, string ...$tags): DiggerComponent
    {
        $query = implode(",", array_map(fn($tag) => "'" . $tag . "'", $tags));

        $this->destroySpeeds->push(CompoundTag::create()
            ->setTag('block', CompoundTag::create()
                ->setString('tags', "query.any_tag(" . $query . ")"))
            ->setInt('speed', $speed)
        );

        return $this;
    }
}