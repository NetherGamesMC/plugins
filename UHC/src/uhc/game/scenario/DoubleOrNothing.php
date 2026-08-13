<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\block\BlockTypeIds;
use pocketmine\event\block\BlockBreakEvent;
use uhc\game\scenario\base\Scenario;
use function mt_rand;

class DoubleOrNothing extends Scenario
{

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if ($block->getDrops($player->getInventory()->getItemInHand()) === []) {
            return;
        }

        switch ($block->getTypeId()) {
            case BlockTypeIds::COAL_ORE:
            case BlockTypeIds::IRON_ORE:
            case BlockTypeIds::GOLD_ORE:
            case BlockTypeIds::DIAMOND_ORE:
            case BlockTypeIds::EMERALD_ORE:
                if (mt_rand(0, 1) === 1) {
                    $event->setXpDropAmount($event->getXpDropAmount() * 2);

                    $drops = [];
                    foreach ($event->getDrops() as $drop) {
                        $drops[] = $drop->setCount($drop->getCount() * 2);
                    }
                    $event->setDrops($drops);
                } else {
                    $event->setXpDropAmount(0);
                    $event->setDrops([]);
                }
                break;
        }
    }
}