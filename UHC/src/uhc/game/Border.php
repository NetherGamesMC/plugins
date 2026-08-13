<?php

declare(strict_types=1);

namespace uhc\game;

use libminigames\Arena;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;

class Border
{
    private const PARTICLE_Y = 7;
    private const PARTICLE_SIZE = 5;
    private const PARTICLE_VIEW_DISTANCE = 20;

    private int $size = 1000;
    private bool $moving = false;

    public function __construct(private Arena $arena)
    {
    }

    public function isMoving(): bool
    {
        return $this->moving;
    }

    public function shrinkTo(int $size, callable $onFinish): void
    {
        if ($this->size > $size) {
            $this->size--;
            $this->moving = true;
            $this->arena->getScoreboard()->setLine($this->arena->getPlayers(), UHCArena::LINE_BORDER, CustomIcon::BORDER . "§a$this->size");
        } elseif ($this->size === $size && $this->moving) {
            $this->moving = false;
            $onFinish();
        }
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function isPlayerInsideOfBorder(Player $p): bool
    {
        $playerPos = $p->getPosition();
        if (
            $playerPos->getFloorX() > $this->getX() || $playerPos->getFloorX() < $this->getX(true) ||
            $playerPos->getFloorZ() > $this->getZ() || $playerPos->getFloorZ() < $this->getZ(true)
        ) {
            return false;
        }

        return true;
    }

    public function getX(bool $isNegative = false): int
    {
        return $isNegative ? (-$this->size) : ($this->size);
    }

    public function getZ(bool $isNegative = false): int
    {
        return $isNegative ? (-$this->size) : ($this->size);
    }

    public function renderParticles(Player $player): void
    {
        $floorX = $player->getPosition()->getFloorX();
        $floorY = $player->getPosition()->getFloorY();
        $floorZ = $player->getPosition()->getFloorZ();
        /** @var Vector3[] $positions */
        $positions = [];

        // Find within these positions where it would intersect minX values
        $minX = min($floorX - self::PARTICLE_VIEW_DISTANCE, $floorX + self::PARTICLE_VIEW_DISTANCE);
        $minZ = min($floorZ - self::PARTICLE_VIEW_DISTANCE, $floorZ + self::PARTICLE_VIEW_DISTANCE);
        $maxX = max($floorX - self::PARTICLE_VIEW_DISTANCE, $floorX + self::PARTICLE_VIEW_DISTANCE);
        $maxZ = max($floorZ - self::PARTICLE_VIEW_DISTANCE, $floorZ + self::PARTICLE_VIEW_DISTANCE);

        // Check for intersecting x, if this intersects, then we will return an array of
        // addition of z-axis from player position
        if ($minX <= $this->getX(true) && $this->getX(true) <= $maxX) {
            for ($z = -self::PARTICLE_SIZE; $z <= self::PARTICLE_SIZE; $z++) {
                $cordZ = $floorZ + $z;
                if ($this->getZ(true) <= $cordZ && $cordZ <= $this->getZ()) { // min <= x <= max
                    $positions[] = new Vector3($this->getX(true), $floorY, $cordZ);
                }
            }
        }

        if ($minX <= $this->getX() && $this->getX() <= $maxX) {
            for ($z = -self::PARTICLE_SIZE; $z <= self::PARTICLE_SIZE; $z++) {
                $cordZ = $floorZ + $z;
                if ($this->getZ(true) <= $cordZ && $cordZ <= $this->getZ()) { // min <= x <= max
                    $positions[] = new Vector3($this->getX(), $floorY, $cordZ);
                }
            }
        }

        if ($minZ <= $this->getZ(true) && $this->getZ(true) <= $maxZ) {
            for ($x = -self::PARTICLE_SIZE; $x <= self::PARTICLE_SIZE; $x++) {
                $cordX = $floorX + $x;
                if ($this->getX(true) < $cordX && $cordX < $this->getX()) {
                    $positions[] = new Vector3($cordX, $floorY, $this->getZ(true));
                }
            }
        }

        if ($minZ <= $this->getZ() && $this->getZ() <= $maxZ) {
            for ($x = -self::PARTICLE_SIZE; $x <= self::PARTICLE_SIZE; $x++) {
                $cordX = $floorX + $x;
                if ($this->getX(true) <= $cordX && $cordX <= $this->getX()) {
                    $positions[] = new Vector3($cordX, $floorY, $this->getZ());
                }
            }
        }

        foreach ($positions as $pos) {
            for ($y = -1; $y <= self::PARTICLE_Y; $y++) {
                $this->arena->getWorld()->addParticle($pos->add(0, $y, 0), new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))), [$player]);
            }
        }
    }
}
