<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\staff;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomLabels;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\PermissionAttachment;

class TesterRank extends StaffRank
{
    public function setPermissions(PermissionAttachment $attachment): array
    {
        if (NGEssentials::isInDevelopmentMode() || NGEssentials::getInstance()->getServerManager()->getServerType() === ServerManager::SETUP) {
            $attachment->setPermission(Permissions::GAME_BW_ADMIN, true);
            $attachment->setPermission(Permissions::GAME_CQ_ADMIN, true);
            $attachment->setPermission(Permissions::GAME_DUELS_ADMIN, true);
            $attachment->setPermission(Permissions::GAME_MM_ADMIN, true);
            $attachment->setPermission(Permissions::GAME_SW_ADMIN, true);
            $attachment->setPermission(Permissions::GAME_TB_ADMIN, true);

            $attachment->setPermission(DefaultPermissionNames::COMMAND_GAMEMODE_SELF, true);
            $attachment->setPermission(DefaultPermissionNames::COMMAND_SETWORLDSPAWN, true);
            $attachment->setPermission(DefaultPermissionNames::COMMAND_TELEPORT_SELF, true);

            $attachment->setPermission(Permissions::RANK_TESTER, true);

            return [self::getTag(), ...parent::setPermissions($attachment)];
        } else {
            return parent::setPermissions($attachment);
        }
    }

    public function getTag(): string
    {
        return CustomLabels::TESTER;
    }

    public function getName(): string
    {
        return "tester";
    }
}