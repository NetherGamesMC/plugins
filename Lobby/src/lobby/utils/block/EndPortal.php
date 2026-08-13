<?php

namespace lobby\utils\block;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\block\Transparent;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\player\Player;

class EndPortal extends Transparent
{
    /**
     * @return AxisAlignedBB[]
     */
    protected function recalculateCollisionBoxes(): array
    {
        return [AxisAlignedBB::one()->trim(Facing::UP, 1 / 4)];
    }

    private function transfer(Player $player): void
    {
        if (!$player->hasNoClientPredictions()) {
            $player->setNoClientPredictions();
            NGEssentials::getInstance()->getPlayerManager()->transferPlayer($player, ServerManager::MD);
        }
    }

    public function onEntityInside(Entity $entity): bool
    {
        if ($entity instanceof Player) {
            $this->transfer($entity);
            return false;
        }

        return parent::onEntityInside($entity);
    }

    public function onEntityLand(Entity $entity): ?float
    {
        if ($entity instanceof Player) {
            $this->transfer($entity);
            return null;
        }

        return parent::onEntityLand($entity);
    }
}