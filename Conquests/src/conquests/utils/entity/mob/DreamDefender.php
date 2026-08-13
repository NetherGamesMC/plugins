<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace conquests\utils\entity\mob;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class DreamDefender extends BaseMob
{
    public const ATTACK_COOLDOWN = 30;
    public const LOSE_DISTANCE = 2500; // 50 blocks
    /** @var int */
    public int $lifeDuration = 4800;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setSpeed(0.7);
        $this->setMaxHealth(75);
        $this->setHealth(75);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::IRON_GOLEM;
    }

    public function getName(): string
    {
        return 'Dream Defender';
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.9, 1.4);
    }

    public function getResultDamage(int $difficulty = -1): float
    {
        return 3;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if ($source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION) {
            $source->cancel();
        }
    }
}