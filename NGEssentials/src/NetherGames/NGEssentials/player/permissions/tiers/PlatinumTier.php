<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class PlatinumTier extends DiamondTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_PLATINUM, true);

        (new TitanRank())->setPermissions($attachment);
    }

    public function getCredits(): int
    {
        return 25000;
    }

    public function getTag(): string
    {
        return CustomIcon::PLATINUM_TIER;
    }

    public function getName(): string
    {
        return "platinum";
    }

    public function getXPBoost(): ?float
    {
        return 1.5;
    }
}