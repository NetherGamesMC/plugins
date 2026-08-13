<?php

namespace skywars\utils;

use Closure;
use pocketmine\color\Color;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;

final class CircleManager
{
    /** @var Vector2 */
    private Vector2 $midPoint;

    /** @var int */
    private $maxSize;

    /** @var float|int */
    private $borderSize;
    /** @var float|int */
    private $nextBorder;
    /** @var float|int */
    private $lastBorder;

    /** @var int */
    private int $finalSize;
    /** @var int */
    private int $suddenDeath;
    /** @var int */
    private int $countdownTick;
    /** @var int */
    private int $currentTick;

    /** @var array */
    private array $borderData;

    /** @var float */
    private float $velocity = -1.0;

    public function __construct(Vector3 $midPoint, array $config = [], private ?Closure $stateFn = null)
    {
        $this->midPoint = self::toVec2($midPoint);

        $totalSize = 0;
        foreach ($config as $key => $value) {
            // Search for the keys until the final-round, this section will be in
            // orderly manner, hopefully
            if ($key === 'final-round') {
                [$size, $countdown, $death] = explode(";", $value);

                $this->suddenDeath = (int)$death;
                $this->finalSize = (int)$size;
                $this->countdownTick = (int)$countdown;
            } else {
                [$size, $tick] = explode(";", $value);

                $this->borderData[$totalSize] = [(int)$size, $totalSize + (int)$size, (int)$tick];
            }

            $totalSize += (int)$size;
        }

        $this->nextBorder = 0;
        $this->maxSize = $totalSize;
        $this->borderSize = $totalSize;
    }

    private static function toVec2(Vector3 $vec): Vector2
    {
        return new Vector2($vec->getFloorX(), $vec->getFloorZ());
    }

    public function isInsideBorder(Vector3 $player): bool
    {
        return self::toVec2($player)->distance($this->midPoint) <= $this->borderSize;
    }

    /**
     * Marks the current border to reach its final form.
     */
    public function markAsFinal(): void
    {
        $this->borderSize = $this->finalSize;
    }

    /**
     * Returns the countdown of sudden death.
     *
     * @return int
     */
    public function getSuddenDeath(): int
    {
        return $this->countdownTick;
    }

    /**
     * Renders a particle using the basic of trigonometry. These formula can be improved but
     * the calculation took less load than normal squared borders.
     *
     * @param Player $player
     */
    public function renderParticles(Player $player): void
    {
        $playerPos = $player->getPosition();
        if (self::toVec2($playerPos)->distance($this->midPoint) < ($this->borderSize - 10)) {
            return;
        }

        /** @var Vector3[] $positions */
        $positions = [];

        $vec2 = self::toVec2($playerPos);

        $midPoint = $this->midPoint;
        $diffX = $vec2->getFloorX() - $midPoint->getFloorX(); // adjacent
        $diffZ = $vec2->getFloorY() - $midPoint->getFloorY(); // opposite

        $length = $this->borderSize; // hypotenuse
        if ($length <= 1) {
            return;
        }

        // Absolute values of the change in xz positions with midpoint.
        $absX = abs($diffX);
        $absZ = abs($diffZ);

        // A denominator will never be 0
        if ($absX === 0) {
            $refAngle2 = acos(0);
            $normZ = sin($refAngle2) * $length;

            for ($x = -5; $x <= 5; $x++) {
                $positions[] = new Vector3((int)floor($x), $playerPos->getFloorY(), $diffZ >= 0 ? (int)floor($normZ) : -(int)floor($normZ));
            }

            goto skipChecks;
        }

        // Ignore -ve values, in trigonometry, lengths of a triangle are always +ve.
        $refAngle = atan($absZ / $absX);

        // If reference angle is more than 45, then we use x-axis to find
        // the y-axis, and vise versa if less than 45.
        if (rad2deg($refAngle) >= 45) {
            $normX = cos($refAngle) * $length;
            for ($x = $normX - 5; $x <= ($normX + 5); $x++) {
                $refAngle2 = acos($x / $length);

                $normZ = sin($refAngle2) * $length;
                if ($diffX >= 0) {
                    $positions[] = new Vector3((int)floor($x), $playerPos->getFloorY(), $diffZ >= 0 ? (int)floor($normZ) : -(int)floor($normZ));
                } else {
                    $positions[] = new Vector3(-(int)floor($x), $playerPos->getFloorY(), $diffZ >= 0 ? (int)floor($normZ) : -(int)floor($normZ));
                }
            }
        } else {
            $normZ = sin($refAngle) * $length;
            for ($z = $normZ - 5; $z <= ($normZ + 5); $z++) {
                $refAngle2 = asin($z / $length);

                $normX = cos($refAngle2) * $length;
                if ($diffZ >= 0) {
                    $positions[] = new Vector3($diffX >= 0 ? (int)floor($normX) : -(int)floor($normX), $playerPos->getFloorY(), (int)floor($z));
                } else {
                    $positions[] = new Vector3($diffX >= 0 ? (int)floor($normX) : -(int)floor($normX), $playerPos->getFloorY(), (int)-floor($z));
                }
            }
        }

        skipChecks:
        foreach ($positions as $pos) {
            for ($y = -1; $y <= 7; $y++) {
                $player->getWorld()->addParticle($pos->add($midPoint->getFloorX(), $y, $midPoint->getFloorY()), new DustParticle(new Color(255, 0, 138)), [$player]);
            }
        }
    }

    public function tickBorder(): void
    {
        // Definitely not doing anything if the border size reached the final round
        // Note: THE BORDER WILL NOT SYNCHRONIZE WITH THE ARENA TIME.
        if ($this->borderSize > $this->finalSize) {
            $nextBorder = ($this->maxSize - $this->borderSize);
            if ($this->velocity === -1.0 || $nextBorder >= $this->nextBorder) {
                $this->lastBorder = $this->nextBorder;

                [$size, $totalSize, $time] = $this->borderData[$this->nextBorder];

                $this->nextBorder = $totalSize;
                $this->velocity = $size / $time;
                $this->currentTick = 1;

                if ($this->stateFn !== null) {
                    $fn = $this->stateFn;
                    $fn($this->borderSize);
                }
            }

            $this->borderSize = $this->maxSize - ($this->lastBorder + ($this->velocity * $this->currentTick++));
        } elseif ($this->countdownTick <= 0) {
            $velocity = $this->finalSize / $this->suddenDeath;

            $this->borderSize = $this->finalSize - ($velocity * $this->currentTick++);
        } else {
            $this->borderSize = $this->finalSize;

            $this->countdownTick--;
            $this->currentTick = 1;
        }
    }

    public function getBorderSize(): float
    {
        return $this->borderSize;
    }
}
