<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\commands;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\enforcement\Enforcement;
use NetherGames\NGEssentials\player\enforcement\objects\StaffPortalInfo;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;

class WarnCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('warn', $plugin);

        $this->setPermission(Permissions::RANK_CREW);
        $this->setPermissionMessage('command.reserved.staff');
        $this->setAliases(['ban', 'mute', 'punish']);
        $this->setDescription('Issue warnings using this command');
        $this->setUsage('/warn <player>');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                $playerManager = $this->getPlugin()->getPlayerManager();
                $enforcementHandler = $playerManager->getEnforcementHandler();

                $player = $playerManager->getBestMatchingPlayer($args[0]);
                $enforcementHandler->getPortalInfo($player, function ($portalInfo) use ($enforcementHandler, $sender): void {
                    if ($sender->isConnected()) {
                        if ($portalInfo instanceof StaffPortalInfo) {
                            $enforcementHandler->sendPunishmentMenu($sender, $portalInfo);
                        } elseif ($portalInfo === Enforcement::PORTAL_INFO_ERROR) {
                            $sender->sendMessage('§cCould not load player information');
                        } elseif ($portalInfo === Enforcement::PORTAL_INFO_NOT_FOUND) {
                            $sender->sendMessage('§cSorry, that player could not be found.');
                        }
                    }
                });
            } else {
                throw new InvalidCommandSyntaxException();
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}