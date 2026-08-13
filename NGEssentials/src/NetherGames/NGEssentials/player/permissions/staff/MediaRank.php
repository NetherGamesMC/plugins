<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class MediaRank extends StaffRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_MEDIA, true);

        return [self::getTag(), ...parent::setPermissions($attachment), ...(new TitanRank())->setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::MEDIA;
    }

    public function getName(): string
    {
        return "media";
    }
}