<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat;


use Closure;
use JsonException;
use libforms\elements\Dropdown;
use libforms\elements\Toggle;
use libforms\FormManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\filter\ChatFilter;
use NetherGames\NGEssentials\player\chat\kafka\GlobalChatManager;
use NetherGames\NGEssentials\player\chat\kafka\type\TextType;
use NetherGames\NGEssentials\player\chat\types\ChatColor;
use NetherGames\NGEssentials\player\chat\types\ChatType;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\social\PlayerSocialInfo;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use function array_keys;
use function array_map;
use function array_search;
use function array_values;
use function count;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class ChatManager
{
    public const STATUS_OFFLINE = 0;
    public const STATUS_ONLINE_DIFFERENT_SERVER = 1;
    public const STATUS_ONLINE_SAME_SERVER = 2;


    /** @var ChatFilter */
    private ChatFilter $filter;

    /** @var GlobalChatManager */
    private GlobalChatManager $globalChatManager;

    public function __construct(private PlayerManager $manager)
    {
        $this->filter = new ChatFilter();
        $this->globalChatManager = new GlobalChatManager($manager);

        $plugin = $manager->getPlugin();
        $plugin->getServer()->getPluginManager()->registerEvents(new ChatListener($this), $plugin);
    }

    public function getPlugin(): NGEssentials
    {
        return $this->getManager()->getPlugin();
    }

    public function getManager(): PlayerManager
    {
        return $this->manager;
    }

    public function getGlobalChatManager(): GlobalChatManager
    {
        return $this->globalChatManager;
    }

    public static function sendChatSettings(Player $player, NGEssentials $ess, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Chat Settings');

            $playerData = $ess->getPlayerData();
            $chatTypes = ChatTypes::getInstance()->getChatTypesByPlayer($player);
            $selected = $playerData->getInt($player, PlayerData::CHAT_TYPE);
            $values = array_values(array_map(static function (ChatType $chatType): string {
                return $chatType->getDisplayName();
            }, $chatTypes));

            $form->addElement(new Dropdown('Chat Type', $values, isset($chatTypes[$selected]) ? array_search($selected, array_keys($chatTypes), true) : 0, function (Player $player, int $value) use ($chatTypes, $playerData): void {
                $playerData->setValue($player, PlayerData::CHAT_TYPE, array_keys($chatTypes)[$value]);
                $player->sendMessage(TextFormat::GREEN . "You're now in " . array_values($chatTypes)[$value]->getDisplayName());
            }));

            $chatColors = ChatColors::getInstance()->getColorsByPlayer($player);
            $selected = $playerData->getInt($player, PlayerData::CHAT_COLOR);
            $values = array_values(array_map(static function (ChatColor $chatColor): string {
                return $chatColor->getDisplayName();
            }, $chatColors));

            $form->addElement(new Dropdown('Chat Color', $values, isset($chatColors[$selected]) ? array_search($selected, array_keys($chatColors), true) : 0, function (Player $player, int $value) use ($chatColors, $playerData): void {
                $playerData->setValue($player, PlayerData::CHAT_COLOR, array_keys($chatColors)[$value]);
                $player->sendMessage(TextFormat::GREEN . 'You selected ' . array_values($chatColors)[$value]->getDisplayName() . ' as your chat color.');
            }));

            $form->addElement(new Dropdown('DM Privacy Settings', ['Allow from everyone', 'Allow friends only', 'Block all messages'], $playerData->getInt($player, PlayerData::DMS_STATUS), function (Player $player, int $value) use ($playerData) {
                $playerData->setValue($player, PlayerData::DMS_STATUS, $value);
                $player->sendMessage(match ($value) {
                    0 => TextFormat::GREEN . 'You are now allowing direct messages from all players.',
                    1 => TextFormat::GREEN . 'You are now allowing direct messages from friends only.',
                    2 => TextFormat::RED . 'You are now blocking all direct messages from everyone.',
                    default => TextFormat::RED . 'An error occurred while updating your DM privacy settings.'
                });
            }));

            if ($playerData->getInt($player, PlayerData::GUILD) > 0) {
                $form->addElement(new Toggle('Enable guild chat', $playerData->getBool($player, PlayerData::GUILD_CHAT), function (Player $player, bool $value) use ($playerData): void {
                    $playerData->setValue($player, PlayerData::GUILD_CHAT, $value);
                    $player->sendMessage(TextFormat::GREEN . 'You have ' . ($value ? 'enabled' : 'disabled') . ' guild chat.');
                }));
            }

            if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                $form->addElement(new Toggle('Enable ranked chat', $playerData->getBool($player, PlayerData::RANKED_CHAT), function (Player $player, bool $value) use ($playerData): void {
                    $playerData->setValue($player, PlayerData::RANKED_CHAT, $value);
                    $player->sendMessage(TextFormat::GREEN . 'You have ' . ($value ? 'enabled' : 'disabled') . ' ranked chat.');
                }));
                $form->addElement(new Toggle('Enable global chat', $playerData->getBool($player, PlayerData::GLOBAL_CHAT), function (Player $player, bool $value) use ($playerData): void {
                    $playerData->setValue($player, PlayerData::GLOBAL_CHAT, $value);
                    $player->sendMessage(TextFormat::GREEN . 'You have ' . ($value ? 'enabled' : 'disabled') . ' global chat.');
                }));
            }

            $form->sendForm();
        }
    }

    public function loadOfflineMessages(Player $player): void
    {
        MySQLCredentials::executeSelect('offline_messages.get', ['player_name' => $player->getName(), 'player_xuid' => $player->getXuid()], function (array $rows) use ($player) {
            if ($player->isConnected() && count($rows) > 0) {
                foreach ($rows as $row) {
                    try {
                        TextType::fromArray(json_decode($row['message'], true, 512, JSON_THROW_ON_ERROR))->handle([$player]);
                    } catch (JsonException) {
                        $player->sendMessage($row['message']);
                    }
                }

                MySQLCredentials::executeGeneric('offline_messages.delete', ['player_name' => $player->getName(), 'player_xuid' => $player->getXuid()]);
            }
        });
    }

    public function sendOfflineMessage(string $playerIdentifier, string|TextType $message): void
    {
        MySQLCredentials::executeInsert('offline_messages.send', [
            'player' => $playerIdentifier,
            'message' => $message instanceof TextType ? json_encode($message->getArray()) : $message
        ]);
    }

    public function sendGuaranteedMessage(string $playerIdentifier, TextType $textType, ?Closure $callable = null): void
    {
        if ($callable !== null) {
            Utils::validateCallableSignature(function(int $status): void {}, $callable);
        }

        if (($player = $this->getManager()->getPlayerFromIdentifier($playerIdentifier)) !== null && $player->isConnected()) {
            $textType->handle([$player]);

            if ($callable !== null) {
                $callable(self::STATUS_ONLINE_SAME_SERVER);
            }
        } else {
            SocialManager::requestPlayerInfo($playerIdentifier, function (?PlayerSocialInfo $info) use ($textType, $playerIdentifier, $callable): void {
                if ($info === null) {
                    $this->sendOfflineMessage($playerIdentifier, $textType);

                    if ($callable !== null) {
                        $callable(self::STATUS_OFFLINE);
                    }
                } else {
                    $this->getGlobalChatManager()->sendPrivateMessage($textType, [$playerIdentifier]);

                    if ($callable !== null) {
                        $callable(self::STATUS_ONLINE_DIFFERENT_SERVER);
                    }
                }
            });
        }
    }

    public function getFilter(): ChatFilter
    {
        return $this->filter;
    }
}