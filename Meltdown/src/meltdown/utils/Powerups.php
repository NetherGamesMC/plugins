<?php

namespace meltdown\utils;

use meltdown\arena\MDArena;
use pocketmine\entity\Location;
use meltdown\utils\entity\IceBootsEntity;
use meltdown\utils\entity\PowerupEntity;
use meltdown\utils\entity\SlipperyBootsEntity;
use meltdown\utils\entity\SnowBombEntity;
use meltdown\utils\math\ValueWeightWrapper;
use meltdown\utils\math\WeightedDistributionPicker;

abstract class Powerups
{
    /**
     * @phpstan-return list<ValueWeightWrapper>
     */
    public static function getPowerupWeights(): array
    {
        return [
            new ValueWeightWrapper(IceBootsEntity::class, 0.15),
            new ValueWeightWrapper(SnowBombEntity::class, 0.7),
            new ValueWeightWrapper(SlipperyBootsEntity::class, 0.15),
        ];
    }

    public static function spawnRandomPowerup(Location $location, MDArena $arena): PowerupEntity{
        /** @var class-string<PowerupEntity> $entityClass */
        $entityClass = WeightedDistributionPicker::pickWeighted(self::getPowerupWeights());

        $entity = new $entityClass($location, $arena);
        $entity->spawnToAll();
        return $entity;
    }
}