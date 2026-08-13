<?php

declare(strict_types=1);

namespace meltdown\utils\math;

use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;

// Tracks a player's trajectory when falling
class PlayerTrajectory
{
    /** @var Vector3[] The list of all recorded positions. NOTE: this is a Vector3[] not a Position[] */
    protected array $positions = [];

    protected Vector3 $startPosition;

    protected Vector3 $lastPosition;

    protected int $totalTicks = 0;

    public function __construct(Vector3 $startPosition)
    {
        $this->startPosition = $startPosition;
        $this->addPosition($startPosition, 0);
    }

    public function addPosition(Vector3 $position, int $ticksSinceLastUpdate = 1): void
    {
        $this->positions[] = $this->lastPosition = $position;
        $this->totalTicks += $ticksSinceLastUpdate;
    }

    public function getStartPosition(): Vector3
    {
        return $this->startPosition;
    }

    public function getLastPosition(): Vector3
    {
        return $this->lastPosition;
    }

    /**
     * @return Vector3[]
     */
    public function getPositions(): array
    {
        return $this->positions;
    }

    public function getTotalTicks(): int
    {
        return $this->totalTicks;
    }

    /**
     * Was this fonction a fall? Yes if >= 10 ticks in air AND >= 4 blocks fallen total
     * We check between max position and last position, not min position because a player could
     * double jump from a minimum position and land higher
     *
     * @return bool
     */
    public function isFall(): bool
    {
        return $this->totalTicks >= 10 && $this->getMaxHeightPosition()->getY() - $this->lastPosition->getY() >= 4;
    }

    /**
     * If two positions have the maximum height, which one is returned is undefined behavior
     *
     * @return Vector3
     */
    public function getMaxHeightPosition(): Vector3
    {
        $positions = $this->positions;
        usort(
            $positions,
            fn(Vector3 $vec1, Vector3 $vec2) => -($vec1->getY() <=> $vec2->getY())
        );
        return $positions[0];
    }

    /**
     * This is intended to be used for falls, so it works by taking the bottom of the bounding box
     * (the Player's feet) and ray tracing between there and the position before that
     *
     * @param float $xHalfWidth The half-width in the x direction of the object that went through this trajectory
     * @param float $zHalfWidth The half-width in the z direction of the object that went through this trajectory
     * @param null|int[] $filterYValues If not null, this will only return collision blocks with a floor(y value) in this array
     * @return Vector3[]
     *
     */
    public function getCollisionPositions(float $xHalfWidth, float $zHalfWidth, ?array $filterYValues = null): array
    {
        $collisionPositions = [];

        // We precompute this
        $xBBOffset = new Vector3($xHalfWidth, 0, 0);
        $zBBOffset = new Vector3(0, 0, $zHalfWidth);
        $BBOffsets = array_map(
        // Least significant bit is +/- in x, most significant bit is +/- in z
            fn(int $i) => $xBBOffset->multiply((-1) ** ($i & 0b01))->addVector($zBBOffset->multiply((-1) ** ($i >> 1))),
            [0, 1, 2, 3]
        );

        // For some reason it's more intuitive to me to start from the last position
        for ($i = count($this->positions) - 1; $i >= 1; --$i) {
            // We check all 4 corners (in xz) at the bottom of the BB.
            for ($j = 0; $j <= 3; ++$j) {
                $collisionsThisIteration = [];
                $startPoint = $this->positions[$i]->addVector($BBOffsets[$j]);
                $endPoint = $this->positions[$i - 1]->addVector($BBOffsets[$j]);

                if ($startPoint->equals($endPoint)) {
                    $collisionPositions = array_unique(array_merge(
                        $collisionPositions,
                        [
                            $startPoint,
                        ]
                    ));
                    continue;
                }

                $collisionGenerator = VoxelRayTrace::betweenPoints($startPoint, $endPoint);
                foreach ($collisionGenerator as $collision) {
                    $collisionsThisIteration[] = $collision;
                }

                $collisionPositions = array_unique(array_merge(
                    $collisionPositions,
                    array_filter(
                        $collisionsThisIteration,
                        fn(Vector3 $vec) => $filterYValues === null || in_array($vec->getY(), $filterYValues)
                    )
                ));
            }
        }

        return $collisionPositions;
    }
}