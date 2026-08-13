<?php

declare(strict_types=1);

namespace libVanilla\item;

use libVanilla\listener\HopperListener;
use pocketmine\entity\object\ItemEntity;
use pocketmine\world\Position;

final class ItemEntityMovementNotifier
{
    public function __construct(private ItemEntity $entity, private HopperListener $listener)
    {
        $this->check($this->entity->getPosition());
    }

    private function check(Position $position): void
    {
        $this->listener->onItemEntityMove(
            $this->entity,
            $position->getFloorX(),
            $position->getFloorY(),
            $position->getFloorZ(),
            $position->getWorld()
        );
    }

    public function update(): void
    {
        if (!$this->entity->isClosed() && !$this->entity->isFlaggedForDespawn() && $this->entity->getLocation()->isValid()) {
            $this->check($this->entity->getPosition());
        }
    }
}