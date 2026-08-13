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
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;

class GetPosCommand extends BaseCommand
{

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('getpos', $plugin);

        if (NGEssentials::isInDevelopmentMode() || $this->getPlugin()->getServerManager()->getServerType() === ServerManager::SETUP) {
            $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        } else {
            $this->setPermission(Permissions::RANK_SUPERVISOR);
            $this->setPermissionMessage('command.reserved.estaff');
        }
        $this->setAliases(['gp']);
        $this->setDescription('Command used for getting current coordinates');
        $this->setUsage('/getpos <on|off>');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof NGPlayer) {
            if (isset($args[0]) && ($args[0] === 'on' || $args[0] === 'off')) {
                $sender->toggleGameRule('showcoordinates', $args[0] === 'on');
            } else {
                throw new InvalidCommandSyntaxException();
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}