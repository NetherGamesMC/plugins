<?php

declare(strict_types=1);

namespace uhc\game\scenario\base;

class Scenario extends ScenarioListener
{

    public function __construct(private string $name, private string $displayName, private string $description, private bool $alwaysActive = false)
    {
        $this->description = "Description: " . $description;
    }

    public final function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isAlwaysActive(): bool
    {
        return $this->alwaysActive;
    }
}
