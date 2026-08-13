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

class Bedbug extends BaseMob
{
    public const ATTACK_COOLDOWN = 15;
    /** @var int */
    public int $lifeDuration = 1200;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setSpeed(0.85);
        $this->setMaxHealth(30);
        $this->setHealth(30);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SILVERFISH;
    }

    public function getName(): string
    {
        return 'Bedbug';
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.3, 0.4);
    }

    public function getResultDamage(int $difficulty = -1): float
    {
        return 1;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if ($source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION) {
            $source->cancel();
        }
    }
}