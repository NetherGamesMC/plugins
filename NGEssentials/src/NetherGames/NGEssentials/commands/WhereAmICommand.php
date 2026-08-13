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
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class WhereAmICommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('whereami', $plugin);

        $this->setPermissions([Permissions::DEFAULT_COMMAND_PERMISSION]);
        $this->setDescription('Find out what server you are on.');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof NGPlayer) {
            $sender->sendMessage(CustomIcon::MAGNIFYING_GLASS . TextFormat::GREEN . 'You are currently online on ' . TextFormat::YELLOW . $this->getPlugin()->getServerManager()->getUniqueId() . TextFormat::GREEN . '(' . TextFormat::YELLOW . $sender->getProxyId() . TextFormat::GREEN . ')');
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}