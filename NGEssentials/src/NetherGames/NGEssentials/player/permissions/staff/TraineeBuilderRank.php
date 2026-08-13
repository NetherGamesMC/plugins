<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class TraineeBuilderRank extends StaffRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_TRAINEE_BUILDER, true);

        return [self::getTag(), ...parent::setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::TRAINEE_BUILDER;
    }

    public function getName(): string
    {
        return "trainee builder";
    }
}