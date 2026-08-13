<?php

declare(strict_types=1);

namespace libVanilla\block\behaviour;

use pocketmine\inventory\Inventory;

/**
 * above() -> When container is above hopper
 * side() -> When container is on a horizontal side of the hopper
 * below() -> When container is below hopper
 */
interface HopperBehaviour
{

    public function above(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void;

    public function side(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void;

    public function below(Inventory $hopperInventory, Inventory $inventory, int $transferCap): void;
}