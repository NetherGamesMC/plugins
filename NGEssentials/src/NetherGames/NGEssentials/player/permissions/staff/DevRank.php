<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\PermissionAttachment;
use pocketmine\utils\TextFormat;

class DevRank extends TraineeRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_DEVELOPER, true);
        $attachment->setPermission(DefaultPermissionNames::COMMAND_STATUS, true);
        $attachment->setPermission(DefaultPermissionNames::COMMAND_TIMINGS, true);

        return [
            self::getTag(),
            ...parent::setPermissions($attachment),
            ...(NGEssentials::isInDevelopmentMode() ? (new TesterRank())->setPermissions($attachment) : []),
            ...(new TitanRank())->setPermissions($attachment)
        ];
    }

    public function getTag(): string
    {
        return CustomLabels::DEVELOPER;
    }

    public function getName(): string
    {
        return 'dev';
    }

    public function getColor(): string
    {
        return TextFormat::GREEN;
    }
}