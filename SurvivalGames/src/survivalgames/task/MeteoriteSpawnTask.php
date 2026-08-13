<?php

declare(strict_types=1);

namespace survivalgames\task;

use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\scheduler\Task;
use pocketmine\utils\Utils;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

/**
 * Meteorite ray rendering system, as far as I know and understand the concept of 3d vectors, this will be using
 * what is well known as rays. We will be rendering projectile at 5 ticks, which is 0.25 seconds. Wind factor are also
 * accounted for.
 *
 * @package survivalgames\task
 */
class MeteoriteSpawnTask extends Task
{

    /** @var Vector3 */
    private Vector3 $position;
    /** @var Vector3 */
    private Vector3 $velocity;
    /** @var Vector3 */
    private Vector3 $gravity;
    /** @var Vector3 */
    private Vector3 $accelerates;

    /** @var World */
    private World $world;

    public function __construct(Vector3 $origin, World $world)
    {
        $this->world = $world;

        // Do not ask why these values are odd.
        $this->position = $origin->floor();
        $this->velocity = new Vector3(self::random_float(0, 2) - 1, -self::random_float(1, 3), self::random_float(0, 2) - 1);
        $this->gravity = new Vector3(0, -1.2902701882692, 0);
        $this->accelerates = new Vector3(self::random_float(0, 2) - 1, -0.3, self::random_float(0, 2) - 1);
    }

    public static function random_float(float $min, float $max): float
    {
        return ($min + Utils::getRandomFloat() * (abs($max - $min)));
    }

    public function onRun(): void
    {
        $lastTrace = $this->position;
        $this->position = $this->position->addVector($this->velocity)->floor();
        $this->velocity = $this->velocity->addVector($this->gravity)->addVector($this->accelerates);

        // Ray tracing to check for blocks in-between points.
        $lastPoint = $lastTrace;
        foreach (VoxelRayTrace::betweenPoints($lastTrace, $this->position) as $vec) {
            if ($lastPoint->getY() < 0 || $vec->getY() < 0 || $lastPoint->getY() > World::Y_MAX || $vec->getY() > World::Y_MAX) {
                continue;
            }
            if (World::blockHash($vec->getX(), $vec->getY(), $vec->getZ()) === World::blockHash($lastPoint->getX(), $lastPoint->getY(), $lastPoint->getZ())) {
                continue;
            }

            $block = $this->world->getBlock($vec);
            if ($block->getTypeId() !== BlockTypeIds::AIR && $block->getTypeId() !== VanillaBlocks::GLASS()->getTypeId()) {
                $this->position = $lastPoint;

                $this->getHandler()->cancel();
                $this->render();

                return;
            }

            $lastPoint = $vec;
        }

        $this->render();
    }

    private function render(): void
    {
        if ($this->position->getY() < 0) {
            $this->getHandler()->cancel();

            return;
        }

        $this->world->addParticle($this->position, new HugeExplodeSeedParticle());
        $this->world->addSound($this->position, new ExplodeSound());

        // Taken from Explosion.php
        // Keep it simple :/

        $explosionSize = 8;
        $minX = (int)floor($this->position->x - $explosionSize - 1);
        $maxX = (int)ceil($this->position->x + $explosionSize + 1);
        $minY = (int)floor($this->position->y - $explosionSize - 1);
        $maxY = (int)ceil($this->position->y + $explosionSize + 1);
        $minZ = (int)floor($this->position->z - $explosionSize - 1);
        $maxZ = (int)ceil($this->position->z + $explosionSize + 1);

        $explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $list = $this->world->getNearbyEntities($explosionBB);
        foreach ($list as $entity) {
            $entityPos = $entity->getPosition();
            $distance = $entityPos->distance($this->position) / $explosionSize;

            if ($distance <= 1) {
                $this->position->y -= 15; // Allow higher y-vector motion magnitude
                $motion = $entityPos->subtractVector($this->position)->normalize();

                $impact = (1 - $distance) * 0.3;

                $damage = (int)((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

                $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_CUSTOM, $damage);

                $entity->attack($ev);
                $entity->setMotion($motion->multiply($impact * 1.2));
            }
        }
    }
}