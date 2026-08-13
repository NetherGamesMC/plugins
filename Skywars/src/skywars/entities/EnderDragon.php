<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace skywars\entities;

use libPhysX\internal\Rotation;
use libPhysX\PhysX;
use libPhysX\utility\MathX;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use skywars\Skywars;
use skywars\SWArena;
use function array_map;
use function array_rand;
use function mt_rand;

class EnderDragon extends Living
{
    /** @var float */
    public float $speed = 0.27;
    /** @var bool */
    public bool $keepMovement = true;
    /** @var Vector3|null */
    private ?Vector3 $target = null;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setCanSaveWithChunk(false);
        $this->setHasGravity(false);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDER_DRAGON;
    }

    public function getName(): string
    {
        return 'Ender Dragon';
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);

        if ($this->target === null || $this->getLocation()->distance($this->target) <= 1) {
            $this->findNewTarget();
        }

        if ($this->target !== null) {
            $this->moveToTarget();
        }

        return true;
    }

    public function findNewTarget(): void
    {
        $arena = Skywars::getInstance()->getArenaByWorld($this->getWorld());
        if ($arena instanceof SWArena) {
            $vectors = array_map(static function (Player $player): Vector3 {
                return $player->getLocation();
            }, $arena->getAlivePlayers());

            if (count($vectors) !== 0) {
                $target = $vectors[array_rand($vectors)];

                $this->target = $target->add(mt_rand(-5, 5), mt_rand(-5, 5), mt_rand(-5, 5));
            }
        }
    }

    public function moveToTarget(): void
    {
        /** @var Vector3 $motion */
        /** @var Rotation $rotation */
        [$motion, $rotation] = PhysX::calculateMRPhysic($this->getLocation(), $this->target, $this->speed, false, true);

        $this->motion = $motion;
        $this->setRotation($rotation->yaw, $rotation->pitch);

        $this->fetchSurroundingVectors();
    }

    public function fetchSurroundingVectors(): void
    {
        /** @var Skywars $plugin */
        $plugin = Skywars::getInstance();
        $plugin->getBlockQueue()->add(MathX::calculateSphere($this->getPosition(), 5), VanillaBlocks::AIR(), $this->getPosition()->getWorld());
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() !== EntityDamageEvent::CAUSE_SUFFOCATION) {
            $this->findNewTarget();
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(4, 13);
    }
}