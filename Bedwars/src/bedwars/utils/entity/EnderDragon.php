<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
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

namespace bedwars\utils\entity;

use bedwars\Bedwars;
use bedwars\BWTeam;
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
use function array_diff;
use function array_map;
use function array_rand;
use function count;
use function mt_rand;

class EnderDragon extends Living
{
    /** @var float */
    public float $speed = 0.27;
    /** @var Vector3|null */
    private ?Vector3 $target = null;
    /** @var BWTeam */
    private BWTeam $team;

    public function __construct(Location $location, BWTeam $team, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->team = $team;
        $this->keepMovement = true;

        $this->setCanSaveWithChunk(false);
        $this->setHasGravity(false);

        $this->setNameTagAlwaysVisible();
        $this->setNameTag($team->getDisplayName() . " Teams's Dragon");
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
        $arena = $this->team->getArena();
        $attackable = $arena->isOpponentlessGame() ? (
        $arena->getAlivePlayers()
        ) : (
        array_diff($arena->getAlivePlayers(), $this->team->getAlivePlayers())
        );

        $vectors = array_map(static function (Player $player): Vector3 {
            return $player->getLocation();
        }, $attackable);

        if (count($vectors) !== 0) {
            $target = $vectors[array_rand($vectors)];

            $this->target = $target->add(mt_rand(-5, 5), mt_rand(-5, 5), mt_rand(-5, 5));
        }
    }

    public function moveToTarget(): void
    {
        if ($this->target === null) {
            return;
        }
        /** @var Vector3 $motion */
        /** @var Rotation $rotation */
        [$motion, $rotation] = PhysX::calculateMRPhysic($this->getLocation(), $this->target, $this->speed, false, true);

        $this->motion = $motion;
        $this->setRotation($rotation->yaw, $rotation->pitch);

        $this->fetchSurroundingVectors();
    }

    public function fetchSurroundingVectors(): void
    {
        /** @var Bedwars $plugin */
        $plugin = Bedwars::getInstance();
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