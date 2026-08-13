<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class GoldTier extends SilverTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_GOLD, true);
    }

    public function getCredits(): int
    {
        return 4000;
    }

    public function getTag(): string
    {
        return CustomIcon::GOLD_TIER;
    }

    public function getName(): string
    {
        return "gold";
    }

    public function getXPBoost(): ?float
    {
        return 0.2;
    }
}