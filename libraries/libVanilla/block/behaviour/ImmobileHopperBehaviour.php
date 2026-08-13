<?php

declare(strict_types=1);

namespace libVanilla\block\behaviour;

use pocketmine\inventory\Inventory;

final class ImmobileHopperBehaviour implements HopperBehaviour
{
    public static function getInstance(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function above(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
    }

    public function side(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
    }

    public function below(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void
    {
    }
}