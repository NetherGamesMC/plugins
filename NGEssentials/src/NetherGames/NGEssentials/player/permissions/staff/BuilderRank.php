<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class BuilderRank extends StaffRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $attachment->setPermission(Permissions::RANK_BUILDER, true);

        $attachment->setPermission(Permissions::PLOT_CREATIVE_UNLIMITED, true);
        $attachment->setPermission(Permissions::PLOT_MEGA_UNLIMITED, true);
        $attachment->setPermission(Permissions::PLOT_PLATINUM_UNLIMITED, true);

        return [
            self::getTag(),
            ...parent::setPermissions($attachment),
            ...(NGEssentials::getInstance()->getServerManager()->getServerType() === ServerManager::SETUP ? (new TesterRank())->setPermissions($attachment) : []),
            ...(new TitanRank())->setPermissions($attachment)
        ];
    }

    public function getTag(): string
    {
        return CustomLabels::BUILDER;
    }

    public function getName(): string
    {
        return "builder";
    }
}