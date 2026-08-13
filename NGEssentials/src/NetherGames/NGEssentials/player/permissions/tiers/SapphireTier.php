<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\tiers;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\EmeraldRank;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\permission\PermissionAttachment;

class SapphireTier extends AmethystTier
{
    public function setPermissions(PermissionAttachment $attachment): void
    {
        parent::setPermissions($attachment);

        $attachment->setPermission(Permissions::TIER_SAPPHIRE, true);

        (new EmeraldRank())->setPermissions($attachment);
    }

    public function getCredits(): int
    {
        return 15000;
    }

    public function getTag(): string
    {
        return CustomIcon::SAPPHIRE_TIER;
    }

    public function getName(): string
    {
        return "sapphire";
    }

    public function getXPBoost(): ?float
    {
        return 0.75;
    }
}