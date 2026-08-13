<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class UltraRank extends VoterRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_ULTRA, true);

        if ($pure) {
            $attachment->setPermission(Permissions::PLOT_MEGA_2, true);
            $attachment->setPermission(Permissions::PLOT_PLATINUM_4, true);
        }

        return [self::getTag(), ...parent::setPermissions($attachment, false)];
    }

    public function getTag(): string
    {
        return CustomLabels::ULTRA;
    }

    public function getName(): string
    {
        return "ultra";
    }
}