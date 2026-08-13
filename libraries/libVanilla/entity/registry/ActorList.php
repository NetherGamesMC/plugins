<?php
declare(strict_types=1);

namespace libVanilla\entity\registry;

class ActorList
{

    public function __construct(private string $class, private string $name, private string $newId)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNewId(): string
    {
        return $this->newId;
    }
}
