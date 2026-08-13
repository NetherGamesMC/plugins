<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class TitanRank extends LegendRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_TITAN, true);
        $attachment->setPermission(Permissions::PERK_NICK_CUSTOM, true);
        $attachment->setPermission(Permissions::PERK_NICK_RANDOM, true);

        if ($pure) {
            $permissions = $attachment->getPermissions();

            if (!isset($permissions[Permissions::PLOT_MEGA_UNLIMITED])) {
                $attachment->setPermission(Permissions::PLOT_MEGA_10, true);
            }
            if (!isset($permissions[Permissions::PLOT_PLATINUM_UNLIMITED])) {
                $attachment->setPermission(Permissions::PLOT_PLATINUM_16, true);
            }
        }

        return [self::getTag(), ...parent::setPermissions($attachment, false)];
    }

    public function getTag(): string
    {
        return CustomLabels::TITAN;
    }

    public function getName(): string
    {
        return "titan";
    }
}