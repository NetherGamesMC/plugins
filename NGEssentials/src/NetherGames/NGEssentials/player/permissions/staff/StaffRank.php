<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\VoterRank;
use pocketmine\permission\PermissionAttachment;
use pocketmine\utils\TextFormat;

abstract class StaffRank
{
    /**
     * @return string[] An array of the tags added to the attachment
     */
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::PERK_NICK_CUSTOM, true);
        $attachment->setPermission(Permissions::RANK_TESTER, true);
        $attachment->setPermission(Permissions::PERK_NICK_RANDOM, true);
        $attachment->setPermission(Permissions::BYPASS_CHAT_FILTER, true);

        return (new VoterRank())->setPermissions($attachment);
    }

    abstract public function getName(): string;

    abstract public function getTag(): string;

    public function getColor(): string
    {
        return TextFormat::AQUA;
    }
}