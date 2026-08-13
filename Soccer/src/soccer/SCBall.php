<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer;

use libPhysX\PhysX;
use libPhysX\utility\ConversionX;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\SkinUtils;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class SCBall extends Human
{

    // safe zone - where no collisions have to be detected
    private const X_MIN = 256;
    private const X_MAX = 302;
    private const Y_MIN = 55;
    private const Y_MAX = 58;
    private const Z_MIN = 1305;
    private const Z_MAX = 1333;

    // normal Ids
    private const X_I = 0;
    private const X_D = 1;
    private const Y_I = 2;
    private const Y_D = 3;
    private const Z_I = 4;
    private const Z_D = 5;

    /** @var float How elastic the ball is */
    private const ELASTICITY = 0.55;
    /** @var float The bounce threshold to safeguard physics calculations */
    private const BOUNCE_THRESHOLD = 0.001;
    /** @var float Kicking force */
    private const FORCE = 0.1;

    /** @var Vector3[] */
    private static $mapNormal;
    /** @var Vector3 */
    private Vector3 $gravitationalMotionVectorCache;
    /** @var Vector3 */
    private Vector3 $bounceReflectionVectorCache;
    /** @var bool */
    private bool $inSafeZone = false;
    /** @var Player */
    private Player $lastInteractor;
    /** @var SCArena */
    private SCArena $arena;

    public function __construct(Location $location, SCArena $arena)
    {
        parent::__construct($location, self::getModel());

        $this->setCanSaveWithChunk(false);

        $this->gravitationalMotionVectorCache = new Vector3(0, 0, 0);
        $this->bounceReflectionVectorCache = new Vector3(0, 0, 0);

        $this->arena = $arena;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.75, 0.75);
    }

    /**
     * Get the soccer ball game-model.
     *
     * @return Skin
     */
    public static function getModel(): Skin
    {
        $ess = NGEssentials::getInstance();
        $folder = 'skins' . DIRECTORY_SEPARATOR . 'objects' . DIRECTORY_SEPARATOR . 'soccerball' . DIRECTORY_SEPARATOR;

        /** @var resource $geometry */
        $geometry = $ess->getResource($folder . 'soccer_ball.json');
        /** @var string $geometryContent */
        $geometryContent = stream_get_contents($geometry);

        $skin = new Skin(
            'SoccerBall',
            SkinUtils::getTextureFromResources($folder . 'soccer_ball.png'),
            '',
            'geometry.object.soccer_ball',
            $geometryContent
        );

        fclose($geometry);

        return $skin;
    }

    /**
     * Setup normals at startup to avoid
     * reconstruction where possible.
     *
     * @return void
     */
    public static function setup(): void
    {
        self::$mapNormal = [
            self::X_I => new Vector3(1, 0, 0),
            self::Y_I => new Vector3(0, 1, 0),
            self::Z_I => new Vector3(0, 0, 1),
            self::X_D => new Vector3(-1, 0, 0),
            self::Y_D => new Vector3(0, -1, 0),
            self::Z_D => new Vector3(0, 0, -1)
        ];
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->getArena()->isRunning()) {
            $teams = $this->getArena()->getTeams();
            foreach ($teams as $team) {
                if ($team->isInGoal($this->getLocation())) {
                    $player = $this->getLastInteractor();
                    $t = $this->getArena()->getTeam($player);

                    if ($t instanceof SCTeam) {
                        $t->addGoal($player, $team === $t);
                    }
                }
            }
        }

        // check for safe zone
        $this->checkForSafeZone();
        if ($this->inSafeZone) {
            return $hasUpdate;
        }

        // collision detection
        $collidedFaceNormalId = $this->getCollidedFaceNormalId();
        if ($collidedFaceNormalId === null) {
            return $hasUpdate;
        }

        // calculate the actual motion vector
        $trueMotionVector = $this->motion->addVector($this->gravitationalMotionVectorCache)->subtractVector($this->bounceReflectionVectorCache);

        // calculate the reflected motion vector
        $idealBounceReflection = PhysX::reflectVector($trueMotionVector, self::$mapNormal[$collidedFaceNormalId]);
        $realisticBounceReflection = $idealBounceReflection->multiply(self::ELASTICITY);

        // check for threshold reaches
        $realisticBounceReflectionAbs = $realisticBounceReflection->abs();
        if ($realisticBounceReflectionAbs->x <= self::BOUNCE_THRESHOLD) {
            $realisticBounceReflection->x = 0;
        }
        if ($realisticBounceReflectionAbs->y <= self::BOUNCE_THRESHOLD) {
            $realisticBounceReflection->y = 0;
        }
        if ($realisticBounceReflectionAbs->z <= self::BOUNCE_THRESHOLD) {
            $realisticBounceReflection->z = 0;
        }

        // assign to motion
        $this->motion = $realisticBounceReflection;

        // save for duplication cancelling
        $this->bounceReflectionVectorCache = $realisticBounceReflection;

        return $hasUpdate;
    }

    public function getArena(): SCArena
    {
        return $this->arena;
    }

    public function getLastInteractor(): Player
    {
        return $this->lastInteractor;
    }

    /**
     * Check if the ball is in the safe zone
     * where no collision detection needs to
     * be done saving compute time.
     *
     * @return void
     */
    private function checkForSafeZone(): void
    {
        $pos = $this->getLocation();

        if ($pos->x >= self::X_MIN && $pos->x <= self::X_MAX &&
            $pos->y >= self::Y_MIN && $pos->y <= self::Y_MAX &&
            $pos->z >= self::Z_MIN && $pos->z <= self::Z_MAX) {
            $this->inSafeZone = true;
            return;
        }
        $this->inSafeZone = false;
    }

    /**
     * Get the collided face normal Id.
     * Returns null if no face was collided.
     *
     * @return int|null
     */
    private function getCollidedFaceNormalId(): ?int
    {
        // define collision checking order
        $order = $this->generateCollisionExecutionOrder();

        // execute collision checking in that order
        for ($i = 0; $i < 3; $i++) {
            if ($order[$i] === 'x') {
                $id = $this->getXCollidedFaceNormalId();
                if ($id === null) {
                    continue;
                }
                return $id;
            }
            if ($order[$i] === 'y') {
                $id = $this->getYCollidedFaceNormalId();
                if ($id === null) {
                    continue;
                }
                return $id;
            }
            if ($order[$i] === 'z') {
                $id = $this->getZCollidedFaceNormalId();
                if ($id === null) {
                    continue;
                }
                return $id;
            }
        }

        return null;
    }

    /**
     * Generate a string based execution order
     * for the collision checking.
     *
     * @return string
     */
    private function generateCollisionExecutionOrder(): string
    {
        // default order
        $x = 0;
        $y = 1;
        $z = 2;

        // change order
        if ($this->motion->y > $this->motion->x) {
            $c = $x;
            $x = $y;
            $y = $c;
        }
        if ($this->motion->z > $this->motion->y) {
            $c = $y;
            $y = $z;
            $z = $c;
        }
        if ($this->motion->z > $this->motion->x && $z > $x) {
            $c = $x;
            $x = $z;
            $z = $c;
        }

        // convert to string
        $priorityToOrder = [
            $x => 'x',
            $y => 'y',
            $z => 'z'
        ];
        ksort($priorityToOrder);
        return implode('', $priorityToOrder);
    }

    /**
     * Get the collided face normal Id for
     * x-axis collisions.
     * Returns null if no face was collided.
     *
     * @return int|null
     */
    private function getXCollidedFaceNormalId(): ?int
    {
        if ($this->isCollidedHorizontally) {
            $splitX = ConversionX::splitFloat($this->getLocation()->x);
            if ($splitX[ConversionX::FLOAT_WHOLE] === (float)(self::X_MIN - 1)) {
                return self::X_I;
            }
            if ($splitX[ConversionX::FLOAT_WHOLE] === (float)(self::X_MAX + 1)) {
                return self::X_D;
            }
        }
        return null;
    }

    /**
     * Get the collided face normal Id for
     * y-axis collisions.
     * Returns null if no face was collided.
     *
     * @return int|null
     */
    private function getYCollidedFaceNormalId(): ?int
    {
        if ($this->isCollidedVertically) {
            $splitY = ConversionX::splitFloat($this->getLocation()->y);
            if ($splitY[ConversionX::FLOAT_WHOLE] === (float)(self::Y_MIN - 1)) {
                return self::Y_I;
            }
            if ($splitY[ConversionX::FLOAT_WHOLE] === (float)(self::Y_MAX + 1)) {
                return self::Y_D;
            }
        }
        return null;
    }

    /**
     * Get the collided face normal Id for
     * z-axis collisions.
     * Returns null if no face was collided.
     *
     * @return int|null
     */
    private function getZCollidedFaceNormalId(): ?int
    {
        if ($this->isCollidedHorizontally) {
            $splitZ = ConversionX::splitFloat($this->getLocation()->z);
            if ($splitZ[ConversionX::FLOAT_WHOLE] === (float)(self::Z_MIN - 1)) {
                return self::Z_I;
            }
            if ($splitZ[ConversionX::FLOAT_WHOLE] === (float)(self::Z_MAX + 1)) {
                return self::Z_D;
            }
        }
        return null;
    }

    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->getArena()->isInArena($player)) {
            // we'll add vertical force later
            $directionVector = $player->getDirectionVector();
            $this->lastInteractor = $player;
            $horizontalDirectionVector = new Vector3($directionVector->x, 0, $directionVector->z);

            // calculate dribble vector
            $dribbleVector = $this->getMotion()->addVector($horizontalDirectionVector->multiply(self::FORCE));

            // add vertical force
            $kickVector = $dribbleVector->add(0, self::FORCE * 0.75, 0);
            $this->setMotion($kickVector);
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            /** @var Player $player */
            $player = $source->getDamager();

            if ($this->getArena()->getTeamNull($player) !== null) {
                // we'll add vertical force later
                $directionVector = $player->getDirectionVector();
                $this->lastInteractor = $player;
                $horizontalDirectionVector = new Vector3($directionVector->x, 0, $directionVector->z);

                // calculate dribble vector
                $dribbleVector = $this->getMotion()->addVector($horizontalDirectionVector->multiply(self::FORCE * 1.5));

                // add vertical force
                $kickVector = $dribbleVector->add(0, self::FORCE * 2, 0);
                $this->setMotion($kickVector);
            }

            $source->cancel();
        } elseif ($source->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $this->getArena()->spawnBall();
        } else {
            $source->cancel();
        }
    }

    /**
     * A hack to get PocketMine's gravitational
     * motion vector.
     *
     *
     */
    protected function tryChangeMovement(): void
    {
        parent::tryChangeMovement();
        $this->gravitationalMotionVectorCache = new Vector3($this->motion->x, $this->motion->y, $this->motion->z);

        // to avoid floating point errors
        if (abs($this->gravitationalMotionVectorCache->x) <= self::MOTION_THRESHOLD) {
            $this->gravitationalMotionVectorCache->x = 0;
        }
        if (abs($this->gravitationalMotionVectorCache->y) <= self::MOTION_THRESHOLD) {
            $this->gravitationalMotionVectorCache->y = 0;
        }
        if (abs($this->gravitationalMotionVectorCache->z) <= self::MOTION_THRESHOLD) {
            $this->gravitationalMotionVectorCache->z = 0;
        }
    }

}