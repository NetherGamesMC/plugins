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
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\ChatTypes;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ChatCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('chat', $plugin);

        $this->setPermissions([Permissions::DEFAULT_COMMAND_PERMISSION]);
        $this->setAliases(['c']);
        $this->setDescription('Toggle between different chats');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof NGPlayer) {
            if (isset($args[0])) {
                $chatTypes = ChatTypes::getInstance();
                $saveId = $chatTypes->getSaveIdByAlias($args[0]);

                if ($saveId !== null && ($chat = $chatTypes->getChatType($saveId))->canBeUsed($sender)) {
                    $playerData = $this->getPlugin()->getPlayerData();
                    $previousSaveId = $playerData->getInt($sender, PlayerData::CHAT_TYPE);

                    if (count($args) > 1) {
                        // Temporarily update the chat type to the one we want to change to
                        $playerData->setValue($sender, PlayerData::CHAT_TYPE, $saveId);
                        $message = implode(' ', array_slice($args, 1));

                        // Strip / from the message if it's the first character
                        if (str_starts_with($message, "/")) {
                            $message = substr($message, 1);
                        }

                        $sender->chat($message);
                        // Restore the previous chat type
                        $playerData->setValue($sender, PlayerData::CHAT_TYPE, $previousSaveId);
                    } else {
                        if ($previousSaveId === $saveId) {
                            $saveId = 0;
                            $chat = $chatTypes->getChatType($saveId);
                        }

                        $playerData->setValue($sender, PlayerData::CHAT_TYPE, $saveId);
                        $sender->sendMessage(TextFormat::GREEN . "You're now in " . $chat->getDisplayName());
                    }
                    return true;
                }
            }

            ChatManager::sendChatSettings($sender, $this->getPlugin());
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}