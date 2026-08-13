<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;

use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class LegendRank extends EmeraldRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_LEGEND, true);
        $attachment->setPermission(Permissions::PERK_NICK_RANDOM, true);

        if ($pure) {
            $attachment->setPermission(Permissions::PLOT_MEGA_10, true);
            $attachment->setPermission(Permissions::PLOT_PLATINUM_16, true);
        }

        return [self::getTag(), ...parent::setPermissions($attachment, false)];
    }

    public function getTag(): string
    {
        return CustomLabels::LEGEND;
    }

    public function getName(): string
    {
        return "legend";
    }
}
