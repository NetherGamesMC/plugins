<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\permission\PermissionAttachment;

class VoterRank extends Rank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_VOTER, true);

        if ($pure) {
            $attachment->setPermission(Permissions::PLOT_MEGA_2, true);
        }

        return parent::setPermissions($attachment);
    }
}