<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\scheduler\ClosureTask;
use uhc\game\scenario\base\Scenario;
use uhc\UHC;

class HasteyBoys extends Scenario
{

    public function onCraftItem(CraftItemEvent $event): void
    {
        $items = $event->getOutputs();
        foreach ($items as $item) {
            if ($item instanceof TieredTool && !$item instanceof Sword) {
                $newItem = clone $item;
                $newItem->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3));
                $newItem->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
                UHC::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($item, $newItem, $event): void {
                    $index = 0;
                    $inventory = $event->getPlayer()->getCursorInventory();
                    $value = $inventory->getItem($index)->equals($item);

                    if (!$value) {
                        foreach ($event->getPlayer()->getInventory()->getContents(true) as $slot => $inventoryItem) {
                            if ($inventoryItem->equals($item)) {
                                $value = true;
                                $inventory = $event->getPlayer()->getInventory();
                                $index = $slot;
                                break;
                            }
                        }
                    }

                    if ($value) {
                        $inventory->setItem($index, $newItem);
                    }
                }));
            }
        }
    }
}