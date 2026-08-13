<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\LegendRank;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class DiamondTier extends SapphireTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_DIAMOND, true);
        $attachment->setPermission(Permissions::PERK_NICK_CUSTOM, true);

        (new LegendRank())->setPermissions($attachment);
    }

    public function getCredits(): int
    {
        return 20000;
    }

    public function getTag(): string
    {
        return CustomIcon::DIAMOND_TIER;
    }

    public function getName(): string
    {
        return "diamond";
    }

    public function getXPBoost(): ?float
    {
        return 1;
    }
}