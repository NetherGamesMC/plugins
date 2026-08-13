<?php

declare(strict_types=1);


namespace libReplay\session\replay\utils;


use pocketmine\entity\Entity;

class ReplayIdConverter
{
    /** @var int[] */
    private array $mapping = [];

    public function addEntity(int $internalId): int
    {
        return $this->mapping[$internalId] = Entity::nextRuntimeId();
    }

    /**
     * @return int[]
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function removeEntity(int $internalId): void
    {
        unset($this->mapping[$internalId]);
    }

    public function getRuntimeId(int $internalId): ?int
    {
        return $this->mapping[$internalId] ?? null;
    }
}