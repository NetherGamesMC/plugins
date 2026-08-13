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

use conquests\CQTeam;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function round;

class MiniSkeleton extends BaseMob
{
    public const ATTACK_COOLDOWN = 15;
    /** @var int */
    public int $lifeDuration = 600;

    public bool $emerging = true;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setSpeed(0.85);
        $this->setMaxHealth(3);
        $this->setHealth(1);
        $this->setScale(0.6);
        $this->setNameTagAlwaysVisible(false);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SKELETON;
    }

    public function getName(): string
    {
        return 'Mini Skeleton';
    }

    public function isEmerging(): bool
    {
        return $this->emerging;
    }

    protected function setTeam(CQTeam $team): void
    {
        parent::setTeam($team);

        $this->getArmorInventory()->setItem(0, VanillaItems::LEATHER_CAP()->setCustomColor($team->getDyeColor()->getRgbValue()));
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6, 1.71);
    }

    public function getResultDamage(int $difficulty = -1): float
    {
        return 0.5;
    }

    public function getDynamicNameTag(): string
    {
        return $this->team->getColor() . round($this->lifeDuration / 20, 1) . 's ';
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if ($this->emerging) {
            $this->setPosition($this->location->add(0, 0.1, 0));
            if (!$this->getWorld()->getCollisionBlocks($this->getBoundingBox(), true)) {
                $this->emerging = false;
            }

            $hasUpdate = true;
        }

        return $hasUpdate;
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($this->emerging) {
            $source->cancel();
        }

        parent::attack($source);

        if (!$source->isCancelled()) {
            // Once it get attacked, the life bar will be displayed.
            $this->setNameTagAlwaysVisible(true);
        }
    }
}