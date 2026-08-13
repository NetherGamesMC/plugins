<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class AmethystTier extends OpalTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_AMETHYST, true);
    }

    public function getCredits(): int
    {
        return 10000;
    }

    public function getTag(): string
    {
        return CustomIcon::AMETHYST_TIER;
    }

    public function getName(): string
    {
        return "amethyst";
    }

    public function getXPBoost(): ?float
    {
        return 0.50;
    }
}