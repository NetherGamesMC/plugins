<?php

declare(strict_types=1);


namespace libReplay\session\replay\utils;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidConverter
{
    /** @var UuidInterface[] */
    private array $mapping;

    public function addEntity(UuidInterface $uuid): UuidInterface
    {
        return $this->mapping[$uuid->getBytes()] = Uuid::uuid4();
    }

    public function removeEntity(UuidInterface $uuid): void
    {
        unset($this->mapping[$uuid->getBytes()]);
    }

    public function getRuntimeUuid(UuidInterface $uuid): ?UuidInterface
    {
        return $this->mapping[$uuid->getBytes()] ?? null;
    }
}