<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\Leaves;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use uhc\game\scenario\base\Scenario;
use function mt_rand;

class CutClean extends Scenario
{
    private const APPLE_RARITY = 6;

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if ($block->getDrops($player->getInventory()->getItemInHand()) === []) {
            return;
        }

        if ($block instanceof Leaves) {
            if (mt_rand(1, 100) <= self::APPLE_RARITY) {
                $event->setDrops([VanillaItems::APPLE()]);
            } elseif (mt_rand(0, 100) <= 4) {
                $event->setDrops([VanillaItems::STRING()]);
            }
        } elseif ($block->getTypeId() === BlockTypeIds::GRAVEL) {
            switch ($block->getTypeId()) {
                case BlockTypeIds::GRAVEL:
                    $event->setDrops([VanillaItems::FEATHER()]);
                    break;
                case BlockTypeIds::IRON_ORE:
                    if (mt_rand(1, 10) > 3) {
                        $event->setXpDropAmount(1);
                    }
                    $event->setDrops([VanillaItems::IRON_INGOT()]);
                    break;
                case BlockTypeIds::GOLD_ORE:
                    $event->setXpDropAmount(1);
                    $event->setDrops([VanillaItems::GOLD_INGOT()]);
                    break;
                case BlockTypeIds::LAPIS_LAZULI_ORE:
                    if (mt_rand(0, 100) <= 21) {
                        $event->setDrops([VanillaItems::BOOK()]);
                    }
                    break;
            }
        }

    }
}