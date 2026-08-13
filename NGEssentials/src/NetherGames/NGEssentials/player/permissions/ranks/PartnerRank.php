<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class PartnerRank extends YouTubeRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_PARTNER, true);

        return [
            self::getTag(),
            ...parent::setPermissions($attachment, false),
            ...(new TitanRank())->setPermissions($attachment, $pure)
        ];
    }

    public function getTag(): string
    {
        return CustomLabels::PARTNER;
    }

    public function getName(): string
    {
        return "partner";
    }
}