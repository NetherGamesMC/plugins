<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use pocketmine\permission\PermissionAttachment;

abstract class Tier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {

    }

    abstract public function getCredits(): int;

    abstract public function getTag(): string;

    abstract public function getName(): string;

    public function getXPBoost(): ?float
    {
        return null;
    }
}