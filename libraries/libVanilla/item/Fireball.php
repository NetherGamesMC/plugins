<?php

declare(strict_types=1);


namespace libVanilla\item;


use libVanilla\entity\object\Fireball as FireballEntity;
use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\ItemUseResult;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Fireball extends ProjectileItem
{
    public function getThrowForce(): float
    {
        return 1.5;
    }

    public function getCooldownTicks(): int
    {
        return 10;
    }

    public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems): ItemUseResult
    {
        if ($player->hasItemCooldown($this)) {
            return ItemUseResult::NONE();
        }

        $oldItem = clone $this;

        $onClickAir = $this->onClickAir($player, $player->getDirectionVector(), $returnedItems);

        if (!$onClickAir->equals(ItemUseResult::FAIL())) {
            $player->resetItemCooldown($oldItem);
        }

        return $onClickAir;
    }

    protected function createEntity(Location $location, Player $thrower): Throwable
    {
        return new FireballEntity($location, $thrower);
    }
}