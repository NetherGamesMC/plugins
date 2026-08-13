<?php

declare(strict_types=1);


namespace libReplay\session\record\utils;


class RecordIdConverter
{
    /** @var int */
    private int $entityCount = 1;
    /** @var int[] */
    private array $mapping = [];

    public function addEntity(int $runtimeId): int
    {
        return $this->mapping[$runtimeId] = $this->nextInternalId();
    }

    /**
     * Returns a new runtime entity ID for a new entity.
     */
    private function nextInternalId(): int
    {
        return $this->entityCount++;
    }

    public function removeEntity(int $runtimeId): void
    {
        unset($this->mapping[$runtimeId]);
    }

    /**
     * @return int[]
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function getInternalId(int $runtimeId): ?int
    {
        return $this->mapping[$runtimeId] ?? null;
    }
}