<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class EmeraldRank extends UltraRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_EMERALD, true);

        if ($pure) {
            $attachment->setPermission(Permissions::PLOT_MEGA_5, true);
            $attachment->setPermission(Permissions::PLOT_PLATINUM_8, true);
        }

        return [self::getTag(), ...parent::setPermissions($attachment, false)];
    }

    public function getTag(): string
    {
        return CustomLabels::EMERALD;
    }

    public function getName(): string
    {
        return "emerald";
    }
}