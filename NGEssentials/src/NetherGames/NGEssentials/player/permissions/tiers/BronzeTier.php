<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class BronzeTier extends SteelTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_BRONZE, true);
    }

    public function getCredits(): int
    {
        return 1000;
    }

    public function getName(): string
    {
        return "bronze";
    }

    public function getTag(): string
    {
        return CustomIcon::BRONZE_TIER;
    }
}