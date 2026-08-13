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
use NetherGames\NGEssentials\player\enforcement\objects\ReportedPlayerData;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReportCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('report', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases(['r', 'hacker']);
        $this->setDescription('Report a player');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            $playerManager = $this->getPlugin()->getPlayerManager();
            $reportHandler = $playerManager->getEnforcementHandler()->getReportsHandler();

            if (isset($args[0])) {
                $reportedName = $args[0];

                if (($reported = $playerManager->getBestMatchingPlayer($reportedName)) instanceof Player) {
                    $reportHandler->sendPlayerReporter($sender, ReportedPlayerData::fromPlayer($reported));
                } else {
                    NGPlayer::getXuidByName($reportedName, function (?string $xuid, ?string $reportedName) use ($sender, $reportHandler): void {
                        if ($xuid === null) {
                            $sender->sendMessage('§cSorry, that player could not be found.');
                        } else {
                            $reported = $this->getPlugin()->getServer()->getOfflinePlayer($reportedName);
                            $reportHandler->sendPlayerReporter($sender, new ReportedPlayerData($reported, $xuid));
                        }
                    });
                }
            } else {
                $reportHandler->sendReporter($sender);
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}