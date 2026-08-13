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

namespace NetherGames\NGEssentials\player\social;

use libDiscord\DiscordChannel;
use libDiscord\LimitAvoidableDiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Field;
use libDiscord\message\embed\MessageEmbed;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\elements\Toggle;
use libforms\FormManager;
use libforms\SimpleForm;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\social\friends\FriendsManager;
use NetherGames\NGEssentials\player\social\guilds\GuildManager;
use NetherGames\NGEssentials\player\social\guilds\objects\Guild;
use NetherGames\NGEssentials\player\social\party\PartyManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\player\utils\PlayerBaseClass;
use NetherGames\NGEssentials\utils\discord\DiscordUtils;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\NickUtils;
use NetherGames\NGEssentials\utils\skins\SkinStore;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use function array_combine;
use function array_fill;
use function array_search;
use function count;
use function in_array;
use function ltrim;
use function mt_rand;
use function str_pad;

class SocialManager extends PlayerBaseClass
{
    /** @var PartyManager */
    private PartyManager $party;
    /** @var FriendsManager */
    private FriendsManager $friends;
    /** @var GuildManager */
    private GuildManager $guilds;
    /** @var DiscordChannel */
    private DiscordChannel $nickChannel;

    public function __construct(PlayerManager $manager)
    {
        parent::__construct($manager);

        $this->party = new PartyManager($this);
        $this->friends = new FriendsManager($this);
        $this->guilds = new GuildManager($this);

        $this->nickChannel = new LimitAvoidableDiscordChannel([]);

        $plugin = $manager->getPlugin();
        $plugin->getServer()->getPluginManager()->registerEvents(new SocialListener($this), $plugin);
    }

    public function sendSocialMenu(Player $player): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Social Menu');
            $form->setType(SimpleForm::FORM_X3_X2);

            $guildsManager = $this->getGuildsManager();
            $guild = $guildsManager->getGuild($this->getPlugin()->getPlayerData()->getInt($player, PlayerData::GUILD));

            $formCallable = function ($guildInvites) use ($guild, $guildsManager, $form, $player) {
                if (!$player->isConnected()) {
                    return;
                }

                $goBack = function (Player $player): void {
                    $this->sendSocialMenu($player);
                };

                $friendsManager = $this->getFriendsManager();
                if (($inviteCount = count($friendsManager->getRequests($player))) > 0) {
                    $text = TextFormat::AQUA . 'Friends' . TextFormat::EOL . TextFormat::GRAY . $inviteCount . ' request' . ($inviteCount > 1 ? 's' : '');
                } else {
                    $text = TextFormat::AQUA . 'Friends';
                }
                $form->addButton(new ImageButton(SimpleForm::BUTTON_X3_X2_TOP . $text, ImageButton::IMAGE_TYPE_PATH, 'textures/ui/friends', function (Player $player) use ($goBack, $friendsManager) {
                    $friendsManager->sendFriendMenu($player, $goBack);
                }));

                if ($guild !== null) {
                    $text = TextFormat::GREEN . 'Guilds' . TextFormat::EOL . TextFormat::GRAY . $guild->getGuildName() . ' guild';
                } elseif (($inviteCount = count($guildInvites)) > 0) {
                    $text = TextFormat::GREEN . 'Guilds' . TextFormat::EOL . TextFormat::GRAY . $inviteCount . ' invite' . ($inviteCount > 1 ? 's' : '');
                } else {
                    $text = TextFormat::GREEN . 'Guilds';
                }
                $form->addButton(new ImageButton(SimpleForm::BUTTON_X3_X2_TOP . $text, ImageButton::IMAGE_TYPE_PATH, 'textures/ui/guilds', function (Player $player) use ($goBack, $guildsManager) {
                    $guildsManager->sendGuildMenu($player, $goBack);
                }));

                $partyManager = $this->getPartyManager();
                if ($partyManager->isPartyCreator($player)) {
                    $text = TextFormat::YELLOW . 'Parties' . TextFormat::EOL . TextFormat::GRAY . 'You are in your own party';
                } elseif (($party = $partyManager->getParty($player)) !== null) {
                    $text = TextFormat::YELLOW . 'Parties' . TextFormat::EOL . TextFormat::GRAY . $party->getLeaderName() . "'s party";
                } elseif (($inviteCount = count($partyManager->getInvites($player))) > 0) {
                    $text = TextFormat::YELLOW . 'Parties' . TextFormat::EOL . TextFormat::GRAY . $inviteCount . '  invite' . ($inviteCount > 1 ? 's' : '');
                } else {
                    $text = TextFormat::YELLOW . 'Parties';
                }
                $form->addButton(new ImageButton(SimpleForm::BUTTON_X3_X2_TOP . $text, ImageButton::IMAGE_TYPE_PATH, 'textures/ui/parties', function (Player $player) use ($goBack, $partyManager) {
                    $partyManager->sendPartiesMenu($player, $goBack);
                }));

                /** @var NGPlayer $player */
                $enabled = ($guild !== null && $this->getPlugin()->getServerManager()->enableSocialManager()) || count($player->getRankTags()) !== 0 || $player->hasPermission(Permissions::PERK_NICK_RANDOM) || $player->hasPermission(Permissions::PERK_NICK_CUSTOM);
                $form->addButton(new ImageButton(SimpleForm::BUTTON_X3_X2_BOTTOM . ($enabled ? '' : TextFormat::GRAY) . 'NameTag Settings', ImageButton::IMAGE_TYPE_PATH, 'textures/ui/nametag', $enabled ? function (Player $player) use ($goBack) {
                    /** @var NGPlayer $player */
                    $this->sendNametagMenu($player, $goBack);
                } : function (Player $player) {
                    $player->sendMessage(TextFormat::RED . 'You don\'t have permission to use this feature.');
                }));

                $form->addButton(new ImageButton(SimpleForm::BUTTON_X3_X2_BOTTOM . 'Chat Settings', ImageButton::IMAGE_TYPE_PATH, 'textures/ui/settings', function (Player $player) use ($goBack): void {
                    ChatManager::sendChatSettings($player, $this->getPlugin(), $goBack);
                }));

                $form->sendForm();
            };

            if ($guild === null) {
                $guildsManager->getInvites($player, $formCallable);
            } else {
                $formCallable([]);
            }
        }
    }

    /**
     * @param string[] $identifiers
     * @phpstan-param callable(array<string, PlayerSocialInfo|null>): void $callback
     * Gives back the identifier as key
     */
    public static function requestPlayerInfos(array $identifiers, callable $callback): void
    {
        Utils::validateCallableSignature(function (array $info): void {}, $callback);

        NGEssentials::getInstance()->getLogger()->info("Social feature (requestPlayerInfos) is currently unavailable.");

        $callback(array_combine(
            $identifiers,
            array_fill(0, count($identifiers), null)
        ));
    }

    /**
     * @param string $identifier The player xuid or username
     * @phpstan-param callable(PlayerSocialInfo|null): void $callback
     */
    public static function requestPlayerInfo(string $identifier, callable $callback): void
    {
        Utils::validateCallableSignature(function (?PlayerSocialInfo $info): void {}, $callback);

        self::requestPlayerInfos([$identifier], function (array $info) use ($callback, $identifier): void {
            $callback($info[$identifier] ?? null);
        });
    }

    public function getGuildsManager(): GuildManager
    {
        return $this->guilds;
    }

    public function getFriendsManager(): FriendsManager
    {
        return $this->friends;
    }

    public function getPartyManager(): PartyManager
    {
        return $this->party;
    }

    public function sendNametagMenu(NGPlayer $player, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $plugin = $this->getPlugin();
            $playerData = $plugin->getPlayerData();
            $guild = $this->getGuildsManager()->getGuild($playerData->getInt($player, PlayerData::GUILD));

            $form->setTitle('Nametag');

            if ($guild !== null && $plugin->getServerManager()->enableSocialManager()) {
                $form->addElement(new Toggle(Translator::getTranslationPlayer($player, "forms.settings.hideguildtag"), $playerData->getBool($player, PlayerData::HIDE_GUILD_TAG), function (Player $player, bool $value) {
                    $playerManager = $this->getManager();

                    $this->getPlugin()->getPlayerData()->setValue($player, PlayerData::HIDE_GUILD_TAG, $value);
                    $player->setNameTag($playerManager->getNameTag($player, TextFormat::YELLOW));

                    Translator::sendMessage($player, "formhandler.settings.guildtag", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, $value ? "formhandler.settings.hiding" : "formhandler.settings.showing")]);
                }));
            }

            $rankTags = $player->getRankTags();
            if (($rankCount = count($rankTags)) !== 0) {
                $rankManager = $this->getManager()->getRankManager();

                $rankTags[] = RankManager::NO_RANK;
                $selectedRank = $playerData->getString($player, PlayerData::SELECTED_RANK);
                $defaultRank = $rankTags[0];
                if ($selectedRank === '') {
                    $selectedRank = $defaultRank;
                } elseif ($selectedRank !== RankManager::NO_RANK) {
                    if (($rank = $rankManager->getRankByName($selectedRank)) === null) {
                        $selectedRank = $defaultRank;
                    } else {
                        $selectedRank = $rank->getTag();
                    }
                }
                $default = array_search($selectedRank, $rankTags, true);

                $form->addElement(new Dropdown('Display Rank', $rankTags, $default === false ? $rankCount - 1 : $default, static function (NGPlayer $player, int $value) use ($plugin, $rankManager, $defaultRank, $rankTags, $rankCount): void {
                    if (($rank = $rankTags[$value]) === $defaultRank) {
                        $plugin->getPlayerData()->setValue($player, PlayerData::SELECTED_RANK, '');
                    } else {
                        $plugin->getPlayerData()->setValue($player, PlayerData::SELECTED_RANK, $rankManager->getNameByTag($rank) ?? RankManager::NO_RANK);
                    }
                    $rankManager->updateNameTag($player);

                    if ($rankCount === $value) {
                        $player->sendMessage(TextFormat::RED . 'Your rank is now hidden.');

                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                    } else {
                        $player->sendMessage(TextFormat::GREEN . 'Your display rank has been changed to ' . $rank);
                    }
                }));
            }

            if (($randomNick = $player->hasPermission(Permissions::PERK_NICK_RANDOM))) {
                $utils = new NickUtils();

                $availableNicks = [];
                for ($i = 0; $i < 5; $i++) {
                    $nick = str_pad($utils::generate(), 16, (string)mt_rand(100000, 999999));
                    if (!Player::isValidUserName($nick)) {
                        $i--;
                        continue;
                    }
                    $availableNicks[] = $nick;
                }

                $form->addElement(new Dropdown(Translator::getTranslationPlayer($player, "forms.settings.nick2"), $availableNicks, -1, function (Player $player, int $value) use ($availableNicks) {
                    $nick = $availableNicks[$value];
                    $this->nickPlayer($player, $nick);
                }));
            }

            $customNick = $player->hasPermission(Permissions::PERK_NICK_CUSTOM);
            if ($randomNick || $customNick) {
                $form->addElement(new Input(Translator::getTranslationPlayer($player, "forms.settings.nick"), $this->getManager()->getPlayerName($player), '', function (Player $player, string $value) use ($customNick) {
                    $value = ltrim(TextFormat::clean($value));
                    $playerManager = $this->getManager();

                    if ($value === 'off' || $value === 'reset') {
                        $this->nickPlayer($player, "");
                    } elseif ($customNick) {
                        $playerManager->checkName($player, $value, function () use ($player, $value) {
                            if (!MySQLCredentials::isDatabaseOnline()) {
                                Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
                                return;
                            }

                            NGPlayer::doesNameExist($value, function (bool $exists) use ($player, $value) {
                                if ($player->isConnected()) {
                                    if ($exists) {
                                        $player->sendMessage('§cAn account with that username already exists, please try again.');
                                    } else {
                                        $this->nickPlayer($player, $value);
                                    }
                                }
                            });
                        });
                    } else {
                        $player->sendMessage("§cYou don't have permission to use a custom nickname.");
                    }
                }));

                $form->addElement(new Toggle("Hide Skin", $playerData->getBool($player, PlayerData::HIDE_NICK_SKIN), static function (Player $player, bool $value) use ($playerData) {
                    $playerData->setValue($player, PlayerData::HIDE_NICK_SKIN, $value);

                    if ($value) {
                        $player->sendMessage(TextFormat::RED . 'Your skin will now be hidden when you nick.');
                    } else {
                        $player->sendMessage(TextFormat::GREEN . 'Your skin will no longer be hidden when you nick.');
                    }

                    if ($playerData->getBool($player, PlayerData::NICK)) {
                        /** @var NGPlayer $player */
                        $player->setSkin($value ? SkinStore::getInstance()->lazyLoad("default", Utils::getRandomFloat() < 0.5 ? "steve" : "alex", "default") : $player->getOriginalSkin());
                        $player->sendSkin();
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public function sendRequestMenu(Player $player, Player $invited): void
    {
        $friendsManager = $this->getFriendsManager();
        $partyManager = $this->getPartyManager();
        $guildsManager = $this->getGuildsManager();

        $form = FormManager::createSimpleForm($player);

        $goBack = function (Player $player) use ($invited) {
            if ($invited->isConnected()) {
                $this->sendRequestMenu($player, $invited);
            } else {
                Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
            }
        };

        if ($form !== null) {
            $form->setTitle('Social Menu: ' . $invited->getName());

            if (!in_array($invited->getName(), $this->getFriendsManager()->getFriends($player), true)) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . 'Add as friend' . TextFormat::EOL . TextFormat::GRAY . 'Send request to ' . $invited->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/friends', static function (Player $player) use ($invited, $friendsManager) {
                    if ($invited->isConnected()) {
                        $friendsManager->sendInvite($player, $invited);
                    } else {
                        Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                    }
                }));
            }
            if (($party = $partyManager->getParty($player)) === null || ($party->getLeaderName() === $player->getName() && $party->getTotalMembers() < $partyManager->getMaxPartySize($player))) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . 'Invite to party' . TextFormat::EOL . TextFormat::GRAY . 'Invite ' . $invited->getName() . ' to your party', ImageButton::IMAGE_TYPE_PATH, 'textures/ui/parties', static function (Player $player) use ($invited, $partyManager) {
                    if ($invited->isConnected()) {
                        $partyManager->invitePlayer($player, $invited);
                    } else {
                        Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                    }
                }));
            }

            if (($playerGuild = $guildsManager->getGuild($this->getPlugin()->getPlayerData()->getInt($player, PlayerData::GUILD))) !== null) {
                if (($invitedGuild = $guildsManager->getGuild($this->getPlugin()->getPlayerData()->getInt($invited, PlayerData::GUILD))) === null) {
                    if ($playerGuild->getGuildRole($player->getName()) >= Guild::RANK_OFFICER) {
                        $form->addButton(new ImageButton(TextFormat::YELLOW . 'Invite to guild' . TextFormat::EOL . TextFormat::GRAY . 'Invite ' . $invited->getName() . ' to ' . $playerGuild->getGuildName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/guilds', static function (Player $player) use ($invited, $playerGuild, $guildsManager) {
                            if ($invited->isConnected()) {
                                $guildsManager->invitePlayer($player, $playerGuild, $invited);
                            } else {
                                Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                            }
                        }));
                    }
                } else {
                    $form->addButton(new ImageButton(TextFormat::YELLOW . 'Guild' . TextFormat::EOL . TextFormat::GRAY . 'View ' . $invited->getName() . '\'s guild', ImageButton::IMAGE_TYPE_PATH, 'textures/ui/guilds', static function (Player $player) use ($invitedGuild, $goBack) {
                        $form = FormManager::createCustomForm($player, $goBack);

                        if ($form !== null) {
                            $form->setTitle("Guild Stats: " . $invitedGuild->getGuildName());

                            foreach ($invitedGuild->getStats() as $value) {
                                $form->addElement(new Label($value));
                            }

                            $form->sendForm();
                        }
                    }));
                }
            }

            $form->addButton(new ImageButton(TextFormat::YELLOW . 'Stats' . TextFormat::EOL . TextFormat::GRAY . 'View ' . $invited->getName() . '\'s statistics', ImageButton::IMAGE_TYPE_FACE, $invited->getName(), function (Player $player) use ($goBack, $invited) {
                Forms::sendStats($player, $invited->getName(), $goBack);
            }));

            $form->sendForm();
        }
    }

    private function nickPlayer(Player $player, string $nick): void
    {
        $plugin = $this->getPlugin();
        $playerData = $plugin->getPlayerData();

        if ($nick === $playerData->getString($player, PlayerData::NICK)) {
            return;
        } else {
            $playerData->setValue($player, PlayerData::NICK, $nick);

            $playerManager = $this->getManager();
            $player->setNameTag($playerManager->getNameTag($player, TextFormat::YELLOW));
            $player->setDisplayName($playerManager->getPlayerColouredName($player));

            if ($plugin->getServerManager()->enableLobbyHandling()) {
                $playerManager->getCosmeticHandler()->equipArmorCosmetics($player);
            }

            $petsManager = $this->getManager()->getPetsManager();
            if ($nick === "") {
                CosmeticHandler::CAPES()->equip($player);
                CosmeticHandler::ATTACHABLES()->equip($player);

                $petsManager->spawnPet($player);

                if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                    $player->setAllowFlight($playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK);
                }

                Translator::sendMessage($player, "formhandler.settings.nick.reset", Translator::TYPE_SUCCESS);
            } else {
                CosmeticHandler::CAPES()->remove($player);
                CosmeticHandler::ATTACHABLES()->unequip($player);
                $petsManager->removePet($player);

                $player->setFlying(false);
                $player->setAllowFlight(false);


                Translator::sendMessage($player, "formhandler.settings.nick.enabled", Translator::TYPE_SUCCESS, TextFormat::AQUA, ...["nametag" => $nick]);

                $this->nickChannel->post(DiscordMessage::embed(MessageEmbed::rich("Nick Logger")
                    ->addFields(
                        Field::simple("Player", $player->getName()),
                        Field::simple("Nick", $nick)
                    )
                    ->setThumbnail(DiscordUtils::asThumbnail($player->getName()))
                ));
            }
        }
    }
}
