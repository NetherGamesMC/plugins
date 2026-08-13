<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class ModRank extends CrewRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_MOD, true);

        return [self::getTag(), ...parent::setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::MOD;
    }

    public function getName(): string
    {
        return "mod";
    }
}