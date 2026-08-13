<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\block\Leaves;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use uhc\game\scenario\base\Scenario;
use function mt_rand;

class GoldenTrees extends Scenario
{
    private const APPLE_RARITY = 5;

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if ($block instanceof Leaves) {
            if (mt_rand(1, 100) === self::APPLE_RARITY) {
                $event->setDrops([VanillaItems::GOLDEN_APPLE()]);
            }
        }
    }
}