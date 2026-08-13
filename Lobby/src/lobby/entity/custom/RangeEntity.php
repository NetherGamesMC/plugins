<?php

declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\features\range\ShootingRange;
use lobby\utils\BaseTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

class RangeEntity extends Living
{
    use BaseTrait;

    public function __construct(private ?ShootingRange $shootingRange, Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setHasGravity(false);
    }

    public static function getNetworkTypeId(): string
    {
        return "ng:lobby_target_air";
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        $source->cancel();
    }

    public function getShootingRange(): ?ShootingRange
    {
        return $this->shootingRange;
    }

    public function getName(): string
    {
        return "air_balloon";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1);
    }
}