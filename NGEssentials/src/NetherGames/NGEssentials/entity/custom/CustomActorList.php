<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\entity\custom;

class CustomActorList
{
    public function __construct(private string $name, private string $id)
    {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
