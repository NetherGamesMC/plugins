<?php

declare(strict_types=1);

namespace skywars\entities;

use libVanilla\entity\hostile\Skeleton;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use skywars\Skywars;
use skywars\SWArena;

class SWSkeleton extends Skeleton
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setCanSaveWithChunk(false);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);
        return true;
    }

    public function isInteresting(Entity $entity): bool
    {
        if (parent::isInteresting($entity)) {
            $owner = $this->getOwningEntity();

            if ($entity instanceof Player && $owner instanceof Player) {
                $main = Skywars::getInstance();

                /** @var SWArena|null $targetArena */
                $targetArena = $main->getArena($entity);
                /** @var SWArena|null $ownerArena */
                $ownerArena = $main->getArena($owner);

                if ($entity === $owner || $targetArena === null || $ownerArena === null || $targetArena->getTeam($entity) === $ownerArena->getTeam($owner)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}