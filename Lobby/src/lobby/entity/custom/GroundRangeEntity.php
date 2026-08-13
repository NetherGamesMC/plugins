<?php

declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\features\range\ShootingRange;
use lobby\utils\BaseTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

class GroundRangeEntity extends RangeEntity
{
    use BaseTrait;

    public function __construct(?ShootingRange $shootingRange, Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($shootingRange, $location, $nbt);
    }

    public static function getNetworkTypeId(): string
    {
        return "ng:lobby_target_ground";
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        $source->cancel();
    }

    public function getName(): string
    {
        return "target_ground";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1);
    }
}