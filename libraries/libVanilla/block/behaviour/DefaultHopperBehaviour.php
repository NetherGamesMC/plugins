<?php

declare(strict_types=1);

namespace libVanilla\block\behaviour;

use pocketmine\inventory\Inventory;

final class DefaultHopperBehaviour implements HopperBehaviour
{
    public static function getInstance(): self
    {
        static $instance = null;
        return $instance ??= new self();
    }

    public function above(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        self::doTransferring($inventory, $hopperInventory, $transferCap);
    }

    public static function doTransferring(Inventory $from, Inventory $to, int $transferCap): bool
    {
        for ($slot = 0, $max = $from->getSize(); $slot < $max; ++$slot) {
            $item = $from->getItem($slot);
            if (!$item->isNull()) {
                $residue_count = 0;
                $deducted_count = min($item->getCount(), $transferCap);
                foreach ($to->addItem($item->pop($deducted_count)) as $residue) {
                    $residue_count += $residue->getCount();
                }
                if ($residue_count !== $deducted_count) {
                    $item->setCount($item->getCount() + $residue_count);
                    $from->setItem($slot, $item);
                    return true;
                }
            }
        }
        return false;
    }

    public function side(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        self::doTransferring($hopperInventory, $inventory, $transferCap);
    }

    public function below(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
        self::doTransferring($hopperInventory, $inventory, $transferCap);
    }
}