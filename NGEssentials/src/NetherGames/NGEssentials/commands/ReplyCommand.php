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
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReplyCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('reply', $plugin);

        $permissions = Permissions::STAFF_RANKS;
        $permissions[] = Permissions::RANK_LEGEND;

        $this->setPermissions($permissions);
        $this->setPermissionMessage('command.reply.noperm');
        $this->setAliases(['r']);
        $this->setDescription('Reply to your last private message');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (($player = $this->getPlugin()->getPlayerData()->getString($sender, PlayerData::REPLY_PLAYER)) !== '') {
                $this->getPlugin()->getServer()->dispatchCommand($sender, 'tell "' . $player . '" ' . implode(' ', $args));
                return true;
            }
            $sender->sendMessage('§cYou haven\'t privately conversed with anyone lately!');
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}