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

use Generator;
use GlobalLogger;
use NetherGames\NGEssentials\elasticsearch\ElasticSearch;
use NetherGames\NGEssentials\elasticsearch\entry\IndexEntry;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\emojis\Emojis;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\message\TranslatedMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\ChatText;
use NetherGames\NGEssentials\player\chat\kafka\type\PlayerWhisperText;
use NetherGames\NGEssentials\player\chat\types\ChatType;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_shift;
use function count;
use function date;
use function implode;
use function mt_rand;
use function strtolower;

class TellCommand extends BaseCommand
{
    public const PREFIX = TextFormat::GOLD . "WHISPER" . TextFormat::WHITE . " » ";

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('tell', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases(['msg', 't', 'w', 'whisper']);
        $this->setDescription('Send a private message to another player');
        $this->setUsage('/tell <player> <message>');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (count($args) > 1) {
                if (strtolower($sender->getName()) === strtolower($args[0])) {
                    Translator::sendMessage($sender, "command.tell.yourself", Translator::TYPE_ERROR);
                } else {
                    $playerName = $args[0];
                    array_shift($args);
                    $message = Emojis::getInstance()->replace(implode(' ', $args));

                    Await::f2c(function () use ($sender, $playerName, $message): Generator {
                        $playerManager = $this->getPlugin()->getPlayerManager();
                        $chatManager = $playerManager->getChatManager();
                        $filter = $chatManager->getFilter();

                        if (!$sender->hasPermission(Permissions::BYPASS_CHAT_FILTER)) {
                            if (!$filter->checkSpam($sender, $message) || !$filter->checkAdvertising($sender, $message)) {
                                return;
                            }

                            $filter->checkSwearing($sender, $message, yield);
                            yield Await::ONCE;
                        }

                        $isStaff = Permissions::isStaff($sender);
                        $friendsManager = $playerManager->getSocialManager()->getFriendsManager();

                        if (($player = $playerManager->getBestMatchingPlayer($playerName)) instanceof Player) {
                            $playerData = $this->getPlugin()->getPlayerData();
                            $isFriend = $friendsManager->isFriend($player, $sender->getName());
                            $status = $playerData->getInt($player, PlayerData::DMS_STATUS);

                            if ($isStaff || ($status === 0 || ($status === 1 && $isFriend))) {
                                /** @var NGPlayer $sender */
                                $warning = ($isFriend || $isStaff) ? "" : TextFormat::EOL . TextFormat::RED . 'Is this message abusive or inappropriate? ' . TextFormat::GOLD . 'Take a screenshot and report it - ' . TextFormat::GREEN . 'ngmc.co/r' . TextFormat::GOLD . ' or ' . TextFormat::GREEN . '#report on Discord (ngmc.co/d)';
                                $sender->sendMessage(self::PREFIX . Translator::getTranslationPlayer($sender, "command.tell.sender", Translator::TYPE_INFO, ...["receiver" => $player->getName(), "message" => $message]));

                                $playerData->setValue($player, PlayerData::REPLY_PLAYER, $sender->getName());

                                //we're using NGPlayer->sendChat() instead of Translator::sendMessage because we want private messages to be compatible with Xbox's player blocking system.
                                $sender->sendChat(self::PREFIX . Translator::getTranslationPlayer($player, "command.tell.receiver", Translator::TYPE_INFO, ...["sender" => $sender->getName(), "message" => $message]) . $warning, [$player]);
                                $this->sendEntry($sender, $player->getXuid(), $player->getName(), $message);
                            } elseif ($status === 2) {
                                $sender->sendMessage(TextFormat::AQUA . $player->getName() . TextFormat::RED . ' has blocked all direct messages.');
                            } else {
                                $sender->sendMessage(TextFormat::AQUA . $player->getName() . TextFormat::RED . ' does not accept direct messages from you.');
                            }
                        } else {
                            $isFriend = $friendsManager->isFriend($sender, $playerName);

                            MySQLCredentials::executeSelect('player.load_dms_status', ['player' => $playerName], yield, yield Await::REJECT);
                            $rows = yield Await::ONCE;

                            if (!$sender->isConnected()) {
                                return;
                            }

                            if (count($rows) > 0) {
                                $row = $rows[0];
                                $status = (int)$row['dms_status'];
                                $playerXuid = $row['xuid'];

                                if (!$isStaff) {
                                    if ($status === 2) {
                                        $sender->sendMessage(TextFormat::AQUA . $playerName . TextFormat::RED . ' has blocked all direct messages.');
                                        return;
                                    } elseif ($status !== 0 && ($status !== 1 || !$isFriend)) {
                                        $sender->sendMessage(TextFormat::AQUA . $playerName . TextFormat::RED . ' does not accept direct messages from you.');
                                        return;
                                    }
                                }

                                $this->sendEntry($sender, $playerXuid, $playerName, $message);
                            } else {
                                Translator::sendMessage($sender, "formhandler.switcher.doesntexist", Translator::TYPE_ERROR);
                                return;
                            }

                            $chatManager->sendGuaranteedMessage($playerXuid, new PlayerWhisperText(
                                new TranslatedMessage(
                                    "command.tell.receiver",
                                    Translator::TYPE_INFO,
                                    ["sender" => $sender->getName(), "message" => $message]
                                ),
                                $sender->getXuid(),
                                $sender->getName()
                            ), function (int $status) use ($sender, $playerName, $message): void {
                                if (!$sender->isConnected()) {
                                    return;
                                }

                                $sender->sendMessage(self::PREFIX . Translator::getTranslationPlayer($sender, "command.tell.sender", Translator::TYPE_INFO, ...["receiver" => $playerName, "message" => $message]));
                                if ($status === ChatManager::STATUS_OFFLINE) {
                                    $sender->sendMessage('§6That player is currently not online, but they will receive your message the next time they join the server.');
                                }
                            });

                            if (($isFriend || $isStaff) && mt_rand(0, 5) === 0) {
                                $chatManager->sendGuaranteedMessage($playerXuid, new ChatText(
                                    new RawMessage(TextFormat::RED . 'Is this message abusive or inappropriate? ' . TextFormat::GOLD . 'Take a screenshot and report it - ' . TextFormat::GREEN . 'ngmc.co/r' . TextFormat::GOLD . ' or ' . TextFormat::GREEN . '#report on Discord (ngmc.co/d)')
                                ));
                            }
                        }
                    }, catches: function (Throwable $error) use ($sender): void {
                        if ($error instanceof SqlError && $sender->isConnected()) {
                            Translator::sendMessage($sender, "db.error", Translator::TYPE_ERROR);
                        }

                        if (!($error instanceof SqlError)) {
                            GlobalLogger::get()->logException($error);
                        }
                    });
                }
            } else {
                throw new InvalidCommandSyntaxException();
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }

    private function sendEntry(Player $sender, string $receiverXuid, string $receiverName, string $message): void
    {
        ElasticSearch::getInstance()->addEntry(new IndexEntry(
            index: ChatType::ES_INDEX,
            data: [
                'content' => TextFormat::clean($message),
                'sender_name' => $sender->getName(),
                'sender_xuid' => $sender->getXuid(),
                'receiver_name' => $receiverName,
                'receiver_xuid' => $receiverXuid,
                'timestamp' => date('Y-m-d H:i:s'),
                'server_id' => ServerManager::getServerUniqueId(),
                'context' => 'whisper',
            ]
        ));
    }

}