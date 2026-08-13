<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class YouTubeRank extends LegendRank
{
    public function setPermissions(PermissionAttachment $attachment, bool $pure = true): array
    {
        $attachment->setPermission(Permissions::RANK_YOUTUBE, true);
        $attachment->setPermission(Permissions::PERK_NICK_CUSTOM, true);
        $attachment->setPermission(Permissions::PERK_NICK_RANDOM, true);

        return [self::getTag(), ...parent::setPermissions($attachment, $pure)];
    }

    public function getTag(): string
    {
        return CustomLabels::YOUTUBE;
    }

    public function getName(): string
    {
        return "youtube";
    }
}