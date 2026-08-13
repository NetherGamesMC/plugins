<?php

declare(strict_types=1);

namespace skywars\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;

class SWSplashPotion extends SplashPotion
{
    /** @var EffectInstance[] */
    private array $effectInstances;

    public function __construct(Location $location, ?Entity $shootingEntity, PotionType $potionType, ?CompoundTag $nbt = null, array $effectInstances = [])
    {
        parent::__construct($location, $shootingEntity, $potionType, $nbt);
        $this->effectInstances = $effectInstances;

        $this->setCanSaveWithChunk(false);
    }

    public function getPotionEffects(): array
    {
        return $this->effectInstances;
    }
}