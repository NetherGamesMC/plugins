<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class SupervisorRank extends ModRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_SUPERVISOR, true);

        return [self::getTag(), ...parent::setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::SUPERVISOR;
    }

    public function getName(): string
    {
        return "supervisor";
    }
}