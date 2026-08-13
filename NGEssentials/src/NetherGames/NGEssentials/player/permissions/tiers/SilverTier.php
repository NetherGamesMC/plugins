<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class SilverTier extends BronzeTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_SILVER, true);
    }

    public function getCredits(): int
    {
        return 2000;
    }

    public function getTag(): string
    {
        return CustomIcon::SILVER_TIER;
    }

    public function getName(): string
    {
        return "silver";
    }

    public function getXPBoost(): ?float
    {
        return 0.1;
    }
}