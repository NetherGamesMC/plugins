<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\DoublePlant;
use pocketmine\block\Element;
use pocketmine\block\Flower;
use pocketmine\block\Opaque;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\SpawnEgg;
use pocketmine\item\VanillaItems;
use uhc\game\scenario\base\Scenario;
use function array_filter;
use function array_map;
use function array_merge;
use function array_rand;

class FlowerPower extends Scenario
{
    /** @var Item[] */
    private array $allItems;

    public function __construct(string $name, string $displayName, string $description, bool $alwaysActive = false)
    {
        parent::__construct($name, $displayName, $description, $alwaysActive);

        $this->allItems = array_merge(
            array_map(static function (Opaque $block): Item {
                return $block->asItem();
            }, array_filter(VanillaBlocks::getAll(), static function (Block $block): bool {
                return $block instanceof Opaque && !$block instanceof Element && $block->getTypeId() !== BlockTypeIds::BEDROCK;
            })),
            array_filter(VanillaItems::getAll(), static function (Item $item): bool {
                return ItemTypeIds::ENCHANTED_GOLDEN_APPLE !== $item->getTypeId() && !($item instanceof SpawnEgg);
            })
        );
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if (!($block instanceof Flower || ($block instanceof DoublePlant))) {
            return;
        }

        $event->setDrops([$this->allItems[array_rand($this->allItems)]]);
    }
}