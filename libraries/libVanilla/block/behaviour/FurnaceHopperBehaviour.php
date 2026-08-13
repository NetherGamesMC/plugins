<?php

declare(strict_types=1);

namespace libVanilla\block\behaviour;

use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\crafting\FurnaceRecipeManager;
use pocketmine\crafting\FurnaceType;
use pocketmine\inventory\Inventory;
use pocketmine\Server;

class FurnaceHopperBehaviour implements HopperBehaviour
{
    /** @var FurnaceRecipeManager */
    private FurnaceRecipeManager $furnaceRecipeManager;

    public function __construct(FurnaceType $type)
    {
        $this->furnaceRecipeManager = Server::getInstance()->getCraftingManager()->getFurnaceRecipeManager($type);
    }

    public function above(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        assert($inventory instanceof FurnaceInventory);
        $item = $inventory->getResult();
        if (!$item->isNull()) {
            foreach ($hopperInventory->addItem($item->pop(min($item->getCount(), $transferCap))) as $residue) {
                $item->setCount($item->getCount() + $residue->getCount());
            }
            $inventory->setResult($item);
        }
    }

    public function side(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        assert($inventory instanceof FurnaceInventory);
        $fuel = $inventory->getFuel();
        if ($fuel->isNull() || $fuel->getCount() < $fuel->getMaxStackSize()) {
            for ($slot = 0, $max = $hopperInventory->getSize(); $slot < $max; ++$slot) {
                $item = $hopperInventory->getItem($slot);
                if ($fuel->isNull() ? $item->getFuelTime() > 0 : $item->equals($fuel)) {
                    $transferred = min($fuel->getMaxStackSize() - $fuel->getCount(), $item->getCount(), $transferCap);
                    $fuel = (clone $item)->setCount($fuel->getCount() + $transferred);
                    $inventory->setFuel($fuel);
                    $hopperInventory->setItem($slot, $item->setCount($item->getCount() - $transferred));
                    if ($fuel->getCount() >= $fuel->getMaxStackSize()) {
                        break;
                    }
                }
            }
        }
    }

    public function below(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        assert($inventory instanceof FurnaceInventory);
        $smelting = $inventory->getSmelting();
        if ($smelting->isNull() || $smelting->getCount() < $smelting->getMaxStackSize()) {
            for ($slot = 0, $max = $hopperInventory->getSize(); $slot < $max; ++$slot) {
                $item = $hopperInventory->getItem($slot);
                if ($smelting->isNull() ? $this->furnaceRecipeManager->match($item) !== null : $item->equals($smelting)) {
                    $transferred = min($smelting->getMaxStackSize() - $smelting->getCount(), $item->getCount(), $transferCap);
                    $smelting = (clone $item)->setCount($smelting->getCount() + $transferred);
                    $inventory->setSmelting($smelting);
                    $hopperInventory->setItem($slot, $item->setCount($item->getCount() - $transferred));
                    if ($smelting->getCount() >= $smelting->getMaxStackSize()) {
                        break;
                    }
                }
            }
        }
    }
}