<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class SteelTier extends Tier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_STEEL, true);
    }

    public function getCredits(): int
    {
        return 500;
    }

    public function getName(): string
    {
        return "steel";
    }

    public function getTag(): string
    {
        return CustomIcon::STEEL_TIER;
    }
}