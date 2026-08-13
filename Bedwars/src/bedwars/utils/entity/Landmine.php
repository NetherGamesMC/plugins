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
 * @author cooldogedev
 *
 */
declare(strict_types=1);

namespace bedwars\utils\entity;

use bedwars\BWTeam;
use bedwars\utils\world\Explosion;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\sound\PressurePlateActivateSound;
use pocketmine\world\sound\PressurePlateDeactivateSound;

final class Landmine extends Projectile
{
    public const LANDMINE_SPACING = 2; // The spacing between landmines

    private const ACTIVATION_TIME = 20 * 3; // How long it takes for the entity to activate
    private const STAY_TIME = 20 * 60 * 20; // How long the entity stays after being activated

    private const LINGER_COUNTDOWN = 20 * 2; // How long the lingering potion takes to start
    private const LINGER_DURATION = 20 * 15; // How long the lingering potion lasts
    private const LINGER_RADIUS_MAX = 5; // The maximum radius of the lingering potion
    private const LINGER_RADIUS_MIN = 2; // The minimum radius of the lingering potion

    private const TIME_BEFORE_DISTANCE_CHECK = 3; // The time before the entity checks if the activator is still in the same position

    private ?BWTeam $team = null;
    private ?Player $activator = null;
    private ?Vector2 $activatorPos = null;

    private int $activationTime = self::ACTIVATION_TIME;
    private int $ticksBeforeDespawn = self::STAY_TIME;
    private int $ticksBeforeLinger = self::LINGER_COUNTDOWN;
    private int $timeBeforeDistanceCheck = 0;

    private float $radius = self::LINGER_RADIUS_MIN;

    private bool $exploded = false;
    private bool $firstRun = false;
    private bool $shrinking = false;

    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $nbt);
        $this->setCanSaveWithChunk(false);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setScale(0.65);
        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $location = $this->getLocation();

        if ($this->activationTime > 0) {
            $this->activationTime -= $tickDiff;

            if ($this->activationTime <= 0) {
                $this->team?->broadcastMessage("§cA landmine has been activated!");
            }
            return $hasUpdate;
        }

        if ($this->ticksBeforeDespawn > 0) {
            $this->ticksBeforeDespawn -= $tickDiff;
        } else {
            $this->flagForDespawn();
            return $hasUpdate;
        }

        if ($this->isUnderwater()) {
            if (!$this->isFlaggedForDespawn() && !$this->closed) {
                $this->activatorPos = null;
                $this->activator = null;
                $this->exploded = false;
                $this->team?->broadcastMessage("§cOne of your landmines has been deactivated by water!");
                $this->flagForDespawn();
                $this->broadcastSound(new PressurePlateDeactivateSound(VanillaBlocks::WEIGHTED_PRESSURE_PLATE_LIGHT()));
            }

            return $hasUpdate;
        }

        if ($this->exploded) {
            if ($this->ticksBeforeLinger > 0) {
                $this->ticksBeforeLinger -= $tickDiff;
                return $hasUpdate;
            }

            if (!$this->invisible) {
                $this->setInvisible();
            }

            if ($this->shrinking) {
                $this->radius -= 0.05;
                if ($this->radius <= self::LINGER_RADIUS_MIN) {
                    $this->shrinking = false;
                }
            } else {
                $this->radius += 0.05;
                if ($this->radius >= self::LINGER_RADIUS_MAX) {
                    $this->shrinking = true;
                }
            }

            foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy($this->radius, $this->radius, $this->radius), $this) as $entity) {
                $arena = $this->team?->getArena();

                if ($arena === null) {
                    continue;
                }

                if ($entity instanceof Player && $entity->isSurvival(true) && $arena->isInArena($entity)) {
                    $entity->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), 20 * 10));
                }
            }

            if ($this->firstRun || $this->ticksBeforeDespawn % 10 === 0) {
                if ($this->firstRun) {
                    $this->firstRun = false;
                }

                for ($x = -$this->radius; $x <= $this->radius; $x++) {
                    for ($y = -$this->radius; $y <= $this->radius; $y++) {
                        for ($z = -$this->radius; $z <= $this->radius; $z++) {
                            $pos = $location->add($x, $y, $z);

                            if ($pos->distance($location) > $this->radius) {
                                continue;
                            }

                            if ($pos->distance($location) < $this->radius - 0.5) {
                                continue;
                            }

                            for ($i = 0; $i < 3; $i++) {
                                $dx = $x + (mt_rand() / mt_getrandmax());
                                $dz = $z + (mt_rand() / mt_getrandmax());
                                $location->getWorld()->addParticle($location->add($dx, $y + 1.25, $dz), new DustParticle(match (mt_rand(0, 5)) {
                                    0 => new Color(0, 255, 0),
                                    1 => new Color(0, 200, 0),
                                    2 => new Color(0, 150, 0),
                                    3 => new Color(41, 75, 41),
                                    4 => new Color(80, 98, 58),
                                    5 => new Color(120, 148, 97),
                                }));
                            }
                        }
                    }
                }
            }
        } else if ($this->activator !== null && count(($entities = $this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(1, 0.5, 1), $this))) !== 0) {
            foreach ($entities as $entity) {
                if (!$entity instanceof Player || $this->activator === $entity || !$entity->isSurvival(true)) {
                    continue;
                }

                $this->triggerExplosion();
                break;
            }
        } else if ($this->activator !== null) {
            if ($this->timeBeforeDistanceCheck > 0) {
                $this->timeBeforeDistanceCheck -= $tickDiff;

                if ($this->timeBeforeDistanceCheck <= 0) {
                    $activatorPos = $this->activator->getPosition()->floor();
                    $this->activatorPos = new Vector2($activatorPos->x, $activatorPos->z);
                }
            } else if ($this->activatorPos !== null) {
                $currentPos = $this->activator->getPosition()->floor();
                $vec2 = new Vector2($currentPos->x, $currentPos->z);

                if ($this->activator->isOnline() && (!$this->activator->isOnGround() || $vec2->distance($this->activatorPos) > 1.0)) {
                    $this->triggerExplosion();
                }
            }
        }

        return $hasUpdate;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.5, 0.5);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.01;
    }

    protected function getInitialGravity(): float
    {
        return 0.04;
    }

    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    public static function getNetworkTypeId(): string
    {
        return "ng:bedwars_landmine";
    }

    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->team === null) {
            return;
        }

        if (!$this->team->getArena()->isInArena($player) || !$player->isSurvival(true)) {
            return;
        }

        if ($this->activationTime > 0) {
            return;
        }

        if ($this->activator === $player || $this->exploded || $this->isUnderwater()) {
            return;
        }

        if ($this->team->getArena()->getTeam($player) === $this->team) {
            return;
        }

        if ($this->activator === null) {
            $this->timeBeforeDistanceCheck = self::TIME_BEFORE_DISTANCE_CHECK;
            $this->activator = $player;
        }

        $this->team->broadcastMessage("§f" . $player->getName() . " §chas stepped on one of your landmines!");
        $this->broadcastSound(new PressurePlateActivateSound(VanillaBlocks::WEIGHTED_PRESSURE_PLATE_LIGHT()));
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
        $cause = $source->getCause();

        if ($cause === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION || $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION) {
            $this->triggerExplosion();
        }
    }

    protected function onDispose(): void
    {
        parent::onDispose();
        $this->team?->removeLandmine($this);
    }

    protected function destroyCycles(): void
    {
        $this->team = null;
        parent::destroyCycles();
    }

    public function setTeam(?BWTeam $team): void
    {
        $this->team = $team;
    }

    private function triggerExplosion(): void
    {
        if ($this->exploded || $this->team === null) {
            return;
        }

        $this->firstRun = true;
        $this->exploded = true;
        $this->ticksBeforeDespawn = self::LINGER_DURATION;

        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [AnimateEntityPacket::create("animation.ng.bedwars.landmine.explode", "", "", 0, "", 0.0, [$this->getId()])]);
        $explosion = new Explosion($this->getLocation(), 0.75, $this->team->getArena(), $this, true, true, 0.75);
        $explosion->explodeB();
    }
}
