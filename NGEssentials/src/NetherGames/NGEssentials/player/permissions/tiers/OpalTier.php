<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class OpalTier extends GoldTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_OPAL, true);
        $attachment->setPermission(Permissions::PERK_NICK_RANDOM, true);
    }

    public function getCredits(): int
    {
        return 7000;
    }

    public function getTag(): string
    {
        return CustomIcon::OPAL_TIER;
    }

    public function getName(): string
    {
        return "opal";
    }

    public function getXPBoost(): ?float
    {
        return 0.3;
    }
}