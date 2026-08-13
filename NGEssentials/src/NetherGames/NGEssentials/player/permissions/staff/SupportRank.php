<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class SupportRank extends TraineeRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        return [self::getTag(), ...parent::setPermissions($attachment), ...(new TitanRank())->setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::SUPPORT;
    }

    public function getName(): string
    {
        return "support";
    }
}