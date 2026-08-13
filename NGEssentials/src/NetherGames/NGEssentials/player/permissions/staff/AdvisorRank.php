<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\PermissionAttachment;

class AdvisorRank extends SupervisorRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        $serverManager = NGEssentials::getInstance()->getServerManager();
        if (!$serverManager->isMMOGame()) {
            if ($serverManager->getServerType() === ServerManager::CREATIVE) {
                $attachment->setPermission(Permissions::PLOT_ADMIN, true);
                $attachment->setPermission(Permissions::PLOT_ADMIN_ADDHELPER, true);
                $attachment->setPermission(Permissions::PLOT_ADMIN_REMOVEHELPER, true);
                $attachment->setPermission(Permissions::PLOT_ADMIN_RESET, true);
                $attachment->setPermission(Permissions::PLOT_ADMIN_BUILD_PLOT, true);
                $attachment->setPermission(Permissions::PLOT_ADMIN_BUILD_READ, true);
            }
        }

        return [self::getTag(), ...parent::setPermissions($attachment)];
    }

    public function getTag(): string
    {
        return CustomLabels::ADVISOR;
    }

    public function getName(): string
    {
        return "advisor";
    }
}