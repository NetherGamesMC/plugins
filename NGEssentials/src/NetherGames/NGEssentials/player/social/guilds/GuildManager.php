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
 * @author k3ithos, matcracker, driesboy, larryTheCoder
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\social\guilds;

use Closure;
use Generator;
use libforms\elements\Button;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\FormManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\ChatText;
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\guilds\objects\Guild;
use NetherGames\NGEssentials\player\social\guilds\objects\GuildChannel;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RdKafka\Message;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_filter;
use function count;
use function explode;
use function implode;
use function json_decode;
use function number_format;
use const JSON_THROW_ON_ERROR;

class GuildManager
{
    private const GUILD_TOPIC = "ess_guild";

    private const MAXIMUM_GUILD_MOTD_SIZE = 40;
    private const MAXIMUM_GUILD_NAME_SIZE = 16;
    private const MAXIMUM_GUILD_TAG_SIZE = 8;

    public const GUILD_NAME_BLACKLIST = [
        "owner",
        "dev",
        "developer",
        "admin",
        "director",
        "mod",
        "moderator",
        "youtube",
        "youtuber",
        "titan"
    ];

    public const GUILD_LEVEL_STEPS = [
        0 => [],
        25 => [self::COLOR_GRAY],
        50 => [self::COLOR_GRAY, self::COLOR_AQUA],
        150 => [self::COLOR_GRAY, self::COLOR_AQUA, self::COLOR_YELLOW],
        300 => [self::COLOR_GRAY, self::COLOR_AQUA, self::COLOR_YELLOW, self::COLOR_DARK_GREEN],
        500 => [self::COLOR_GRAY, self::COLOR_AQUA, self::COLOR_YELLOW, self::COLOR_DARK_GREEN, self::COLOR_PINK],
        750 => [self::COLOR_GRAY, self::COLOR_AQUA, self::COLOR_YELLOW, self::COLOR_DARK_GREEN, self::COLOR_PINK, self::COLOR_GOLD]
    ];
    public const COLOR_GRAY = [
        TextFormat::GRAY,
        "Gray"
    ];
    public const COLOR_AQUA = [
        TextFormat::DARK_AQUA,
        "Dark Aqua"
    ];
    public const COLOR_DARK_GREEN = [
        TextFormat::DARK_GREEN,
        "Dark Green"
    ];
    public const COLOR_YELLOW = [
        TextFormat::YELLOW,
        "Yellow"
    ];
    public const COLOR_PINK = [
        TextFormat::LIGHT_PURPLE,
        "Pink"
    ];
    public const COLOR_GOLD = [
        TextFormat::GOLD,
        "Gold",
    ];
    public const COLOR_RED = [
        TextFormat::RED,
        "Red"
    ];
    /** @var Guild[] */
    private array $guilds = [];
    /** @var true[][] */
    private array $reference = [];

    public function __construct(private SocialManager $socialManager)
    {
        $this->registerKafkaTopic($this->socialManager->getPlugin());
    }

    // ---------------------------------------------- FORM MENU FUNCTIONS ----------------------------------------------

    public function sendGuildMenu(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Guilds');
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack) {
                $this->sendGuildMenu($player, $onBack);
            };

            $guildName = $this->getSocialManager()->getPlugin()->getPlayerData()->getInt($player, PlayerData::GUILD);
            if ($guildName > 0) {
                if (($guild = $this->getGuild($guildName)) === null) {
                    return;
                }

                SocialManager::requestPlayerInfos($guild->getMembers(), function (array $info) use ($goBack, $form, $guild, $player): void {
                    if (!$player->isConnected()) {
                        return;
                    }

                    /**
                     * @param ?PlayerSocialInfo[] $info
                     * @phpstan-param array<string, PlayerSocialInfo|null> $info
                     */
                    $onlineCounter = count(array_filter($info, function ($i): bool {
                        return $i !== null;
                    }));

                    $form->addButton(new Button(TextFormat::YELLOW . 'Guild members' . TextFormat::EOL . TextFormat::GRAY . 'Online: ' . TextFormat::GREEN . $onlineCounter . TextFormat::GRAY . ' | Members: ' . TextFormat::GREEN . count($info) . '/' . $guild->getMaxGuildSize(), function (Player $player) use ($goBack, $guild, $info) {
                        $this->sendMembersMenu($player, $guild, $info, $goBack);
                    }));
                    $form->addButton(new Button(TextFormat::YELLOW . 'Guild statistics', function (Player $player) use ($goBack, $guild) {
                        $form = FormManager::createCustomForm($player, $goBack);

                        if ($form !== null) {
                            $form->setTitle('Guild Statistics');

                            $form->addElement(new Label(TextFormat::GREEN . 'Guild Name: ' . TextFormat::WHITE . $guild->getGuildName()));
                            $form->addElement(new Label(TextFormat::GREEN . 'Level: ' . TextFormat::WHITE . number_format($guild->getXpLevel())));
                            $form->addElement(new Label(TextFormat::GREEN . 'XP: ' . TextFormat::WHITE . number_format($guild->getXp())));
                            $form->addElement(new Label(TextFormat::GOLD . TextFormat::ITALIC . number_format(ExperienceUtils::getXpToReachLevel($guild->getXpLevel() + 1) - $guild->getXp()) . ' XP required to reach next level.'));
                            $form->addElement(new Label(TextFormat::GOLD . TextFormat::ITALIC . (($diff = self::getNextColorXPDiff($guild)) !== -1 ? number_format($diff) . ' more XP needed to next color.' : TextFormat::RED . "Highest color tag achieved.")));
                            $form->sendForm();
                        }
                    }));

                    $rank = $guild->getGuildRole($player->getName());

                    if ($rank >= Guild::RANK_OFFICER && count($info) < $guild->getMaxGuildSize() && !$guild->isDisabled()) {
                        $form->addButton(new Button(TextFormat::YELLOW . 'Invite players', function (Player $player) use ($goBack, $guild) {
                            $form = FormManager::createCustomForm($player, $goBack);

                            if ($form !== null) {
                                $form->setTitle('Invite Player');

                                $playerManager = $this->getSocialManager()->getManager();
                                $playersNames = $playerManager->getPlayerNames(array_diff($player->getServer()->getOnlinePlayers(), [$player], $guild->getMembers()));

                                $form->addElement(new Dropdown('Invite more players:', $playersNames, -1, function (Player $player, int $value) use ($playerManager, $guild, $playersNames) {
                                    $playerInvited = $playerManager->getBestMatchingPlayer($playersNames[$value]);

                                    if ($playerInvited instanceof Player) {
                                        $this->invitePlayer($player, $guild, $playerInvited);
                                    } else {
                                        Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                                    }
                                }));

                                $form->sendForm();
                            }
                        }));
                    }

                    if ($rank === Guild::RANK_LEADER) {
                        $form->addButton(new Button(TextFormat::YELLOW . 'Edit MOTD', function (Player $player) use ($goBack, $guild) {
                            if ($guild->isDisabled()) {
                                $player->sendMessage(TextFormat::RED . "You can't use that feature right now - your guild has been disabled because your leader no longer has a " . TextFormat::AQUA . TextFormat::BOLD . "LEGEND " . TextFormat::RESET . TextFormat::RED . "rank.");
                                return;
                            }

                            if ($guild->getXpLevel() < 100) {
                                $player->sendMessage(TextFormat::RED . 'Your guild must be at least level 100 to use this feature!');
                                return;
                            }

                            $form = FormManager::createCustomForm($player, $goBack);

                            if ($form !== null) {
                                $motd = $guild->getMotd();

                                $form->setTitle('Set Guild MOTD');

                                $form->addElement(new Label('Your guild MOTD is displayed to your guild members everytime they join the server.'));
                                $form->addElement(new Input('Enter a MOTD:', $motd === '' ? 'Hi guild members!' : $motd, $motd, function (Player $player, string $motd) use ($guild): void {
                                    if ($motd === '') {
                                        $player->sendMessage(TextFormat::GREEN . 'Your guild MOTD has been reset.');
                                        $guild->setMotd($motd, true);
                                    } else {
                                        $filter = $this->getSocialManager()->getPlugin()->getPlayerManager()->getChatManager()->getFilter();
                                        if (!$filter->checkAdvertising($player, $motd)) {
                                            return;
                                        }

                                        if (strlen($motd) > self::MAXIMUM_GUILD_MOTD_SIZE) {
                                            $player->sendMessage(TextFormat::RED . "Your guild motd cannot exceed more than " . self::MAXIMUM_GUILD_MOTD_SIZE . " characters.");
                                            return;
                                        }

                                        $filter->checkSwearing($player, $motd, function () use ($player, $motd, $guild) {
                                            $guild->setMotd($motd, true);

                                            if ($player->isConnected()) {
                                                $player->sendMessage(TextFormat::GREEN . 'Your guild MOTD has been set.');
                                            }
                                        });
                                    }
                                }));

                                $form->sendForm();
                            }
                        }));
                        $form->addButton(new Button(TextFormat::YELLOW . 'Change guild name', function (Player $player) use ($goBack, $guild): void {
                            if ($guild->isDisabled()) {
                                $player->sendMessage(TextFormat::RED . "You can't use that feature right now - your guild has been disabled because your leader no longer has a " . TextFormat::AQUA . TextFormat::BOLD . "LEGEND " . TextFormat::RESET . TextFormat::RED . "rank.");
                                return;
                            }

                            Await::f2c(function () use ($player, $guild, $goBack): Generator {
                                $form = FormManager::createCustomForm($player, $goBack);

                                if ($form !== null) {
                                    $form->setTitle('Rename Guild');

                                    $form->addElement(new Input('Enter your desired guild name:', $guild->getGuildName(), '', yield Await::RESOLVE_MULTI));
                                    $form->sendForm();

                                    /**
                                     * @var Player $player
                                     * @var string $guildName
                                     */
                                    [$player, $guildName] = yield Await::ONCE;

                                    $guildName = TextFormat::clean(trim(preg_replace("/[ ]+/", " ", $guildName)));
                                    if (strlen($guildName) > self::MAXIMUM_GUILD_NAME_SIZE) {
                                        $player->sendMessage(TextFormat::RED . 'Your guild name can\'t be more than ' . self::MAXIMUM_GUILD_NAME_SIZE . ' characters long.');
                                        return;
                                    }

                                    $this->checkGuildName($player, $guildName, yield);
                                    yield Await::ONCE;

                                    $oldName = $guild->getGuildName();

                                    $guild->renameGuildName($guildName, true, yield);
                                    $status = yield Await::ONCE;

                                    if ($status === Guild::RENAME_GUILD_ERROR) {
                                        Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
                                    } else if ($status === Guild::RENAME_GUILD_EXISTS) {
                                        $player->sendMessage(TextFormat::RED . "A guild with that name already exists.");
                                    } else {
                                        $this->sendGuildMessage($guild, TextFormat::GOLD . "Guild " . TextFormat::YELLOW . $oldName . TextFormat::GOLD . " has been renamed to " . TextFormat::YELLOW . $guildName);

                                        $player->sendMessage(TextFormat::GREEN . "Successfully renamed your guild to " . TextFormat::YELLOW . $guildName . TextFormat::GREEN . '!');
                                    }
                                }
                            });
                        }));
                        $form->addButton(new Button(TextFormat::YELLOW . 'Change guild tag', function (Player $player) use ($goBack, $guild): void {
                            if ($guild->isDisabled()) {
                                $player->sendMessage(TextFormat::RED . "You can't use that feature right now - your guild has been disabled because your leader no longer has a " . TextFormat::AQUA . TextFormat::BOLD . "LEGEND " . TextFormat::RESET . TextFormat::RED . "rank.");
                                return;
                            }

                            if ($guild->getXpLevel() < 25) {
                                $player->sendMessage(TextFormat::RED . 'Your guild must be at least level 25 to use this feature!');
                                return;
                            }

                            $colors = $this->getGuildTagColorLabels($guild, $player);
                            $form = FormManager::createCustomForm($player, $goBack);

                            if ($form !== null) {
                                $clearedTag = TextFormat::clean($guild->getTag());

                                $form->setTitle('Set Guild Tag');

                                $form->addElement(new Label('Your guild tag is displayed at the end of each member\'s nametag in the lobby and can\'t be more than 6 characters long.'));

                                Await::f2c(function () use ($form, $clearedTag, $guild, $colors): Generator {
                                    $form->addElement(new Input('Enter a tag:', $clearedTag === '' ? 'NETHER' : $clearedTag, $clearedTag, yield Await::RESOLVE_MULTI, true));
                                    $form->addElement(new Dropdown('Choose a colour:', $colors, 0, yield Await::RESOLVE_MULTI, true)); // This default value will always have a value.

                                    [[$player, $input], [1 => $index]] = yield Await::ALL;

                                    $tag = ltrim(TextFormat::clean(strtoupper($input)));
                                    if (strlen($tag) > self::MAXIMUM_GUILD_TAG_SIZE) {
                                        $player->sendMessage(TextFormat::RED . 'Your guild tag can\'t be more than ' . self::MAXIMUM_GUILD_TAG_SIZE . ' characters long.');
                                        return;
                                    }

                                    if (!empty($tag)) {

                                        foreach (self::GUILD_NAME_BLACKLIST as $blacklistName) {
                                            if (strtolower($tag) === $blacklistName) {
                                                Translator::sendMessage($player, "guild.name.impersonateRank", Translator::TYPE_ERROR);
                                                return;
                                            }
                                        }
                                        $this->checkGuildName($player, $tag, function () use ($player, $guild, $tag, $index) {
                                            $tag = $this->getGuildTagColors($guild, $player)[$index] . TextFormat::clean($tag);

                                            $guild->setTag($tag, true);

                                            $player->sendMessage(TextFormat::GREEN . 'Your guild tag has been set to ' . TextFormat::BOLD . $tag . TextFormat::RESET . TextFormat::GREEN . '!');
                                        });
                                    } else {
                                        $guild->setTag($tag, true);

                                        $player->sendMessage(TextFormat::GREEN . 'Your guild tag has been reset!');
                                    }
                                });

                                $form->sendForm();
                            }
                        }));
                        $form->addButton(new Button(TextFormat::YELLOW . 'Disband the guild', function (Player $player) use ($goBack, $guild) {
                            $form = FormManager::createCustomForm($player, $goBack);

                            if ($form !== null) {
                                $form->setTitle('Disband Guild');

                                $guildName = $guild->getGuildName();

                                $form->addElement(new Label('Are you sure you want to disband the ' . $guildName . ' guild? ' . TextFormat::RED . 'This action is irreversible!'));
                                $form->addElement(new Input('Type your guild name to confirm', $guildName, '', function (Player $player, string $input) use ($guild): void {
                                    if ($input === $guild->getGuildName()) {
                                        $this->disbandGuild($player, $guild, false, true);
                                    } else {
                                        $player->sendMessage(TextFormat::RED . 'The guild name is incorrect. You must type the guild name exactly as it is named (case-sensitive).');
                                    }
                                }));

                                $form->sendForm();
                            }
                        }));
                    } else {
                        $form->addButton(new Button(TextFormat::YELLOW . 'Leave guild', function (Player $player) use ($goBack, $guild) {
                            $form = FormManager::createModalForm($player);

                            if ($form !== null) {
                                $form->setTitle('Leave Guild');

                                $form->setContent('Are you sure you want to leave the ' . $guild->getGuildName() . ' guild?');

                                $form->setButton1(new Button(TextFormat::GREEN . 'Yes', function (Player $player) use ($guild) {
                                    $player->sendMessage(TextFormat::GREEN . 'You left the ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GREEN . ' guild.');

                                    $guild->removeMember($player->getName(), true);

                                    $this->sendGuildMessage($guild, TextFormat::AQUA . $player->getName() . TextFormat::GOLD . ' left the guild.');
                                }));
                                $form->setButton2(new Button(TextFormat::RED . 'No', $goBack));

                                $form->sendForm();
                            }
                        }));
                    }

                    $form->sendForm();
                });
            } else {
                $form->addButton(new Button(TextFormat::YELLOW . 'Create a guild', function (Player $player) use ($goBack) {
                    if ($player->hasPermission(Permissions::RANK_LEGEND)) {
                        $form = FormManager::createCustomForm($player, $goBack);

                        if ($form !== null) {
                            $form->setTitle('Create Guild');

                            $form->addElement(new Input('Enter your desired guild name:', '', '', function (Player $player, string $input) {
                                $this->createGuild($player, TextFormat::clean(trim(preg_replace("/[ ]+/", " ", $input))));
                            }));

                            $form->sendForm();
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You don\'t have permission to create a guild! Buy the ' . TextFormat::AQUA . TextFormat::BOLD . 'LEGEND ' . TextFormat::RESET . TextFormat::RED . 'rank at ' . TextFormat::AQUA . 'ngmc.co/store ' . TextFormat::GOLD . 'to create one!');
                    }
                }));
                $this->getInvites($player, function ($invites) use ($player, $goBack, $form) {
                    if (!$player->isConnected()) {
                        return;
                    }

                    $form->addButton(new Button(TextFormat::YELLOW . 'View invites' . TextFormat::EOL . TextFormat::GRAY . 'Current invites: ' . count($invites), function (Player $player) use ($invites, $goBack) {
                        $form = FormManager::createSimpleForm($player);

                        if ($form !== null) {
                            $form->setTitle('Guild Invites');
                            $form->setBackClosure($goBack);

                            foreach ($invites as $guildId => $guildName) {
                                $form->addButton(new Button(TextFormat::YELLOW . $guildName . TextFormat::EOL . TextFormat::GRAY . 'Accept or deny this request', function (Player $player) use ($guildId) {
                                    $this->loadGuildById($guildId, function (Guild $guild) use ($player) {
                                        if (!$player->isConnected()) {
                                            return;
                                        }

                                        $form = FormManager::createModalForm($player);

                                        if ($form !== null) {
                                            $form->setTitle('Guild Invite');

                                            $form->setContent('Do you want to join the ' . $guild->getGuildName() . ' guild?' . TextFormat::EOL . TextFormat::EOL . implode(TextFormat::EOL, $guild->getStats()));

                                            $form->setButton1(new Button(TextFormat::GREEN . 'Yes', function (Player $player) use ($guild) {
                                                $this->addGuildMember($player, $guild, function () use ($guild): void {
                                                    $this->collectGuildGarbage($guild);
                                                });
                                            }));
                                            $form->setButton2(new Button(TextFormat::RED . 'No', function (Player $player) use ($guild) {
                                                MySQLCredentials::executeChange("guild_invites.remove_invite", ['guild_id' => $guild->getGuildId(), 'invitee_xuid' => $player->getXuid()], function () use ($player, $guild) {
                                                    if ($player->isConnected()) {
                                                        $player->sendMessage('§6Declined the request to join the §b' . $guild->getGuildName() . '§6 guild.');
                                                    }
                                                });
                                            }));

                                            $form->sendForm();
                                        }
                                    });
                                }));
                            }

                            $form->sendForm();
                        }
                    }));

                    $form->sendForm();
                });
            }
        }
    }

    /**
     * @return SocialManager
     */
    public function getSocialManager(): SocialManager
    {
        return $this->socialManager;
    }

    public function getGuild(int $guildId): ?Guild
    {
        return $this->guilds[$guildId] ?? null;
    }

    /**
     * @param Player $player
     * @param Guild $guild
     * @param ?PlayerSocialInfo[] $info
     * @phpstan-param array<string, PlayerSocialInfo|null> $info
     * @param Closure $onBack
     */
    public function sendMembersMenu(Player $player, Guild $guild, array $info, Closure $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Guild Members');
            $form->setBackClosure($onBack);

            $onlineOfficers = [];
            $onlineMembers = [];
            $offlineOfficers = $guild->getOfficers();
            $offlineMembers = $guild->getMembers(true);

            $goBack = function (Player $player) use ($onBack, $guild, $info) {
                $this->sendMembersMenu($player, $guild, $info, $onBack);
            };

            foreach ($info as $identifier => $i) {
                $rank = $guild->getGuildRole($identifier);

                if ($rank === Guild::RANK_LEADER) {
                    if ($i !== null) {
                        $text = TextFormat::YELLOW . $guild->getLeader() . TextFormat::EOL . TextFormat::GRAY . 'Leader' . TextFormat::GRAY . ' | ' . TextFormat::GREEN . 'Online';
                    } else {
                        $text = TextFormat::YELLOW . $guild->getLeader() . TextFormat::EOL . TextFormat::GRAY . 'Leader' . TextFormat::GRAY . ' | ' . TextFormat::RED . 'Offline';
                    }

                    $form->addButton(new ImageButton($text, ImageButton::IMAGE_TYPE_FACE, $guild->getLeader(), function (Player $player) use ($guild, $goBack) {
                        $this->sendMemberMenu($player, $guild, $guild->getLeader(), Guild::RANK_LEADER, $goBack);
                    }));
                } else if ($i !== null) {
                    if ($rank === Guild::RANK_OFFICER && ($key = array_search($i->playerName, $offlineOfficers)) !== false) {
                        $onlineOfficers[] = $offlineOfficers[$key];

                        unset($offlineOfficers[$key]);
                    } else if (($key = array_search($i->playerName, $offlineMembers)) !== false) {
                        $onlineMembers[] = $offlineMembers[$key];

                        unset($offlineMembers[$key]);
                    }
                }
            }

            foreach ($onlineOfficers as $officer) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . $officer . TextFormat::EOL . TextFormat::GRAY . 'Officer' . TextFormat::GRAY . ' | ' . TextFormat::GREEN . 'Online', ImageButton::IMAGE_TYPE_FACE, $officer, function (Player $player) use ($officer, $guild, $goBack) {
                    $this->sendMemberMenu($player, $guild, $officer, Guild::RANK_OFFICER, $goBack);
                }));
            }

            foreach ($offlineOfficers as $officer) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . $officer . TextFormat::EOL . TextFormat::GRAY . 'Officer' . TextFormat::GRAY . ' | ' . TextFormat::RED . 'Offline', ImageButton::IMAGE_TYPE_FACE, $officer, function (Player $player) use ($officer, $guild, $goBack) {
                    $this->sendMemberMenu($player, $guild, $officer, Guild::RANK_OFFICER, $goBack);
                }));
            }

            foreach ($onlineMembers as $member) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . $member . TextFormat::EOL . TextFormat::GREEN . 'Online', ImageButton::IMAGE_TYPE_FACE, $member, function (Player $player) use ($member, $guild, $goBack) {
                    $this->sendMemberMenu($player, $guild, $member, Guild::RANK_MEMBER, $goBack);
                }));
            }

            foreach ($offlineMembers as $member) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . $member . TextFormat::EOL . TextFormat::RED . 'Offline', ImageButton::IMAGE_TYPE_FACE, $member, function (Player $player) use ($member, $guild, $goBack) {
                    $this->sendMemberMenu($player, $guild, $member, Guild::RANK_MEMBER, $goBack);
                }));
            }

            $form->sendForm();
        }
    }

    // -------------------------------------------------- INVITE CORES -------------------------------------------------

    public function sendMemberMenu(Player $player, Guild $guild, string $member, int $memberRank, ?callable $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Guild Member: ' . $member);
            $form->setBackClosure($onBack);

            $playerRank = $guild->getGuildRole($player);

            if ($playerRank === Guild::RANK_LEADER && $member !== $player->getName()) {
                // Guild promotion
                $form->addButton(new Button(TextFormat::YELLOW . 'Promote to Leader' . TextFormat::EOL . TextFormat::GRAY . 'Make them the guild leader.', function (Player $player) use ($guild, $member) {
                    if (($memberInstance = $player->getServer()->getPlayerExact($member)) instanceof Player) {
                        if ($memberInstance->hasPermission(Permissions::RANK_LEGEND)) {
                            $guild->setLeader($member, true);

                            $this->sendGuildMessage($guild, TextFormat::AQUA . $member . TextFormat::GREEN . ' has been promoted to guild leader.');

                            if ($guild->isDisabled()) {
                                $guild->setDisabled(false, true);

                                $this->sendGuildMessage($guild, TextFormat::GREEN . 'Guild ' . TextFormat::GOLD . $guild->getGuildName() . TextFormat::GREEN . ' has now been re-enabled.');
                            }
                        } else {
                            $player->sendMessage(TextFormat::RED . 'That player does not have the required permissions to take leadership of the guild.');
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'That player must be online for them to transfer leadership of the guild.');
                    }
                }));

                if ($memberRank >= Guild::RANK_OFFICER) {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Demote to Member' . TextFormat::EOL . TextFormat::GRAY . 'Make them a guild member.', function (Player $player) use ($guild, $member) {
                        $guild->setMemberRole($member, Guild::RANK_MEMBER, true);

                        $this->sendGuildMessage($guild, TextFormat::AQUA . $member . TextFormat::GREEN . ' has been demoted to a guild member.');
                    }));
                } elseif ($memberRank === Guild::RANK_MEMBER) {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Promote to Officer' . TextFormat::EOL . TextFormat::GRAY . 'Make them a guild officer.', function (Player $player) use ($guild, $member) {
                        $guild->setMemberRole($member, Guild::RANK_OFFICER, true);

                        $this->sendGuildMessage($guild, TextFormat::AQUA . $member . TextFormat::GREEN . ' has been promoted to guild officer.');
                    }));
                }
            }

            // Kick player from this guild.
            if (($playerRank === Guild::RANK_LEADER || ($playerRank === Guild::RANK_OFFICER && $memberRank === Guild::RANK_MEMBER)) && $member !== $player->getName()) {
                $form->addButton(new Button(TextFormat::YELLOW . 'Kick' . TextFormat::EOL . TextFormat::GRAY . 'Remove them from the guild.', function (Player $player) use ($guild, $member, $memberRank, $onBack) {
                    $form = FormManager::createModalForm($player);
                    if ($form !== null) {
                        $form->setTitle('Kick ' . $member);
                        $form->setContent('Are you sure you want to kick ' . $member . ' from the guild?');

                        $form->setButton1(new Button("Yes", function (Player $player) use ($guild, $member) {
                            $guild->removeMember($member, true);

                            if (($memberInstance = $player->getServer()->getPlayerExact($member)) instanceof Player) {
                                $memberInstance->sendMessage(TextFormat::RED . 'You have been kicked from the ' . $guild->getGuildName() . ' guild by ' . TextFormat::AQUA . $player->getName() . TextFormat::RED . '.');

                                $this->collectGarbage($memberInstance, $guild);
                            }

                            $this->sendGuildMessage($guild, TextFormat::AQUA . $player->getName() . TextFormat::GOLD . ' kicked ' . TextFormat::RED . $member . TextFormat::GOLD . ' from the guild.');
                        }));
                        $form->setButton2(new Button("No", function (Player $player) use ($guild, $member, $memberRank, $onBack) {
                            $this->sendMemberMenu($player, $guild, $member, $memberRank, $onBack);
                        }));
                        $form->sendForm();
                    }
                }));
            }

            $form->addButton(new Button(TextFormat::YELLOW . 'Stats' . TextFormat::EOL . TextFormat::GRAY . 'Show player statistics.', function (Player $player) use ($guild, $member, $memberRank, $onBack) {
                Forms::sendStats($player, $member, function (Player $player) use ($guild, $member, $memberRank, $onBack): void {
                    $this->sendMemberMenu($player, $guild, $member, $memberRank, $onBack);
                });
            }));

            $form->sendForm();
        }
    }

    /**
     * Send a message to all guild members
     *
     * @param Guild $guild
     * @param string $message
     */
    public function sendGuildMessage(Guild $guild, string $message): void
    {
        $this->getSocialManager()->getManager()->getChatManager()->getGlobalChatManager()->sendGuildMessage(
            new ChatText(new RawMessage(TextFormat::DARK_GREEN . 'Guild > ' . $message)),
            $guild->getGuildId()
        );
    }

    /**
     * Player referenced garbage collector, designed to release all guilds referenced to the given player
     * after/when the player creating a guild, joining a guild and leaving a guild. The result value of
     * this reference should and always be 0.
     *
     * @param Player $player
     * @param Guild $guild
     */
    public function collectGarbage(Player $player, Guild $guild): void
    {
        unset($this->reference[$guild->getGuildId()][$player->getName()]);

        $this->collectGuildGarbage($guild);
    }

    /**
     * Guild referenced garbage collector, designed to release all guilds referenced to the given guild
     * after/when the guild is deleted. The result value of this reference should and always be 0.
     *
     * @param Guild $guild
     */
    private function collectGuildGarbage(Guild $guild): void
    {
        if (count($this->reference[$guild->getGuildId()] ?? []) <= 0) {
            $this->getSocialManager()->getPlugin()->getLogger()->warning("Cyclic reference for guild " . $guild->getGuildId() . " has exhausted, removing from cache");

            unset($this->guilds[$guild->getGuildId()]);
            unset($this->reference[$guild->getGuildId()]);
        }
    }

    /**
     * @param Guild $guild
     * @return int
     *
     * Returns -1 when the highest level is already reached, otherwise tries to calculate the next step and returns the experience diff for the diff
     * between the current level and the next step
     */
    public static function getNextColorXPDiff(Guild $guild): int
    {
        if ($guild->getXpLevel() >= array_key_last(self::GUILD_LEVEL_STEPS)) {
            return -1;
        }
        foreach (self::GUILD_LEVEL_STEPS as $step => $_) {
            if ($guild->getXpLevel() < $step) {
                return ExperienceUtils::getXpToReachLevel($step) - ExperienceUtils::getXpToReachLevel($guild->getXpLevel());
            }
        }
        return 0;
    }

    /**
     * Invite $invited into $sender's guild
     *
     * @param Player $inviter
     * @param Guild $guild
     * @param Player $invited
     */
    public function invitePlayer(Player $inviter, Guild $guild, Player $invited): void
    {
        $this->isInvitedByGuild($guild, $invited, function ($isInvitedByGuild) use ($guild, $invited, $inviter) {
            if (!$inviter->isConnected()) {
                return;
            }

            if ($this->isInGuild($invited)) {
                $inviter->sendMessage(TextFormat::AQUA . $invited->getName() . TextFormat::RED . ' is already in a guild!');
            } elseif ($isInvitedByGuild) {
                $inviter->sendMessage(TextFormat::RED . 'You have already invited ' . TextFormat::AQUA . $invited->getName() . TextFormat::RED . ' to ' . $guild->getGuildName() . '.');
            } elseif (count($guild->getMembers()) >= $guild->getMaxGuildSize()) {
                $inviter->sendMessage(TextFormat::RED . 'You can\'t invite more than ' . TextFormat::GOLD . $guild->getMaxGuildSize() . TextFormat::RED . ' to ' . $guild->getGuildName() . '!');
            } elseif ($guild->isDisabled()) {
                $inviter->sendMessage(TextFormat::RED . "You can't use that feature right now - your guild has been disabled because your leader no longer has a " . TextFormat::AQUA . TextFormat::BOLD . "LEGEND " . TextFormat::RESET . TextFormat::RED . "rank.");
            } else {
                MySQLCredentials::executeInsert("guild_invites.add_invite", ['guild_id' => $guild->getGuildId(), 'inviter_xuid' => $inviter->getXuid(), 'invitee_xuid' => $invited->getXuid(),], function () use ($inviter, $invited, $guild) {
                    if ($invited->isOnline()) {
                        $invited->sendMessage(TextFormat::AQUA . $inviter->getName() . TextFormat::GOLD . ' has invited you to join the ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GOLD . ' guild! Use the Social Menu to accept.');
                    }
                    if ($inviter->isOnline()) {
                        $inviter->sendMessage(TextFormat::GREEN . 'Invited ' . TextFormat::AQUA . $invited->getName() . TextFormat::GREEN . ' to ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GREEN . '.');
                    }
                });
            }
        });
    }

    // -------------------------------------------------- GUILD CORES --------------------------------------------------

    /**
     * Check if player is invited by a specific guild
     *
     * @param Guild $inviter
     * @param Player $invited
     * @param callable $callable
     */
    private function isInvitedByGuild(Guild $inviter, Player $invited, callable $callable): void
    {
        $this->getInvites($invited, function ($invites) use ($callable, $inviter) {
            $callable(isset($invites[$inviter->getGuildId()]));
        });
    }

    /**
     * @param Player $invited
     * @param callable $callable (array $guildIds)
     */
    public function getInvites(Player $invited, callable $callable): void
    {
        MySQLCredentials::executeSelect('guild_invites.get_invites', ['invitee_xuid' => $invited->getXuid()], static function (array $rows) use ($callable) {
            if (count($rows) > 0) {
                $data = [];

                foreach ($rows as $row) {
                    $data[$row['guild_id']] = $row['guild_name'];
                }

                $callable($data);
            } else {
                $callable([]);
            }
        });
    }

    public function isInGuild(Player $player): bool
    {
        return $this->getSocialManager()->getPlugin()->getPlayerData()->getInt($player, PlayerData::GUILD) > 0;
    }

    public function checkGuildName(Player $player, string $name, callable $onValid): void
    {
        foreach (['owner', '0wner', '0wn3r', 'admin', '4dmin', '4dm1n', 'mod', 'm0d', 'crew', 'cr3w', 'ultra', 'ultr4', 'legend', 'l3gend', 'leg3nd', 'l3g3nd', 'titan', 't1tan', 'tit4n', 't1t4n'] as $term) {
            if (stripos($name, $term) !== false) {
                Translator::sendMessage($player, "staff.impersonating", Translator::TYPE_WARNING);
                return;
            }
        }

        $this->getSocialManager()->getManager()->checkName($player, $name, $onValid);
    }

    public function getGuildTagColorLabels(Guild $guild, Player $leader): array
    {
        $colors = $this->getGuildColors($guild, $leader);

        return array_map(static function (array $colorData): string {
            return $colorData[0] . $colorData[1];
        }, $colors);
    }

    public function getGuildColors(Guild $guild, Player $leader): array
    {
        $level = $guild->getXpLevel();

        $highest = 0;
        foreach (self::GUILD_LEVEL_STEPS as $step => $_) {
            if ($level >= $step) {
                $highest = $step;
            }
        }

        $colors = self::GUILD_LEVEL_STEPS[$highest];

        if ($leader->hasPermission(Permissions::RANK_TITAN)) {
            $colors[] = self::COLOR_RED;
        }

        return $colors;
    }

    public function getGuildTagColors(Guild $guild, Player $leader): array
    {
        $colors = $this->getGuildColors($guild, $leader);

        return array_map(static function (array $colorData): string {
            return $colorData[0];
        }, $colors);
    }

    // ------------------------------------------------ MISC FUNCTIONS ------------------------------------------------

    /**
     * Attempt to disband a guild, this function can be used by another player (e.g.: Administrators, Executives)
     * without the need of the leader of the guild to perform it.
     *
     * @param Player $player
     * @param Guild $guild
     * @param bool $force
     * @param bool $update
     */
    public function disbandGuild(Player $player, Guild $guild, bool $force = false, bool $update = false): void
    {
        if (!isset($this->guilds[$guild->getGuildId()]) && !$force) {
            $player->sendMessage(TextFormat::RED . "Unable to disband faction, guild were not loaded from database.");
            return;
        }

        $this->sendGuildMessage($guild, TextFormat::AQUA . $player->getName() . TextFormat::GOLD . ' has disbanded the ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GOLD . ' guild.');

        foreach ($guild->getMembers() as $member) {
            $target = NGEssentials::getInstance()->getPlayerManager()->getBestMatchingPlayer($member);
            if ($target instanceof Player) {
                $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                $playerData->setValue($target, PlayerData::GUILD, 0);

                $this->collectGarbage($target, $guild);
            }
        }

        if ($update) {
            Await::f2c(function () use ($player, $guild): Generator {
                MySQLCredentials::executeChange("guild.delete", [
                    "guild_id" => $guild->getGuildId()
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                $player->sendMessage(TextFormat::GREEN . 'Disbanded the ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GREEN . ' guild.');

                $this->broadcastEvent($guild, GuildChannel::EVENT_GUILD_DISBAND);
            }, catches: function (Throwable $error) use ($player) {
                $this->getSocialManager()->getPlugin()->getLogger()->logException($error);

                if ($player->isConnected()) {
                    Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
                }
            });
        }
    }

    /**
     * Attempt to synchronize the guild event to all servers, this is a simple synchronizing system whereas
     * the cached guild object in the server will be updated to the new data given. Their guild will be validated
     * if there is no referenced guilds in the server.
     *
     * @param Guild $guild
     * @param int $updateType
     * @param array $payload
     * @return void
     */
    public function broadcastEvent(Guild $guild, int $updateType, array $payload = [])
    {
        $this->getSocialManager()->getPlugin()->getPublisher()?->publishMessage(
            self::GUILD_TOPIC,
            json_encode($payload),
            implode(":", [$guild->getGuildId(), $updateType])
        );
    }

    public function createGuild(Player $player, string $guildName): void
    {
        Await::f2c(function () use ($player, $guildName): Generator {
            $guildName = TextFormat::clean($guildName);
            if (strlen($guildName) > self::MAXIMUM_GUILD_NAME_SIZE) {
                $player->sendMessage(TextFormat::RED . 'Your guild name can\'t be more than ' . self::MAXIMUM_GUILD_NAME_SIZE . ' characters long.');
                return;
            }

            $this->checkGuildName($player, $guildName, yield);
            yield Await::ONCE;

            // Initialize guilds, these given steps should:
            // 1. Prevent the owner from recreating an existing guilds
            // 2. Prevent the owner from creating guild while over an existing guilds.

            MySQLCredentials::executeSelect("guild.exists", [
                'guild_name' => $guildName
            ], yield, yield Await::REJECT);

            $rows = yield Await::ONCE;
            if (count($rows) > 0) {
                $player->sendMessage(TextFormat::RED . "A guild with that name already exists.");
                return;
            }

            MySQLCredentials::executeInsert("guild.create", [
                'guild_name' => $guildName,
                'leader' => $player->getName(),
            ], yield Await::RESOLVE_MULTI, yield Await::REJECT);

            [$guildId] = yield Await::ONCE;

            MySQLCredentials::executeInsert("guild.add_member", [
                'guild_id' => $guildId,
                'role' => Guild::RANK_LEADER,
                'player_name' => $player->getName()
            ], yield, yield Await::REJECT);

            yield Await::ONCE;

            if ($player->isConnected()) {
                $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                $playerData->setValue($player, PlayerData::GUILD, $guildId);

                $guild = new Guild($this, $guildId, $guildName, $player->getName());

                if (!isset($this->guilds[$guild->getGuildId()])) {
                    $this->guilds[$guild->getGuildId()] = $guild;
                }

                $this->reference[$guild->getGuildId()][$player->getName()] = true;

                $this->sendGuildMenu($player);
            }
        }, catches: function (Throwable $error) use ($player) {
            $this->getSocialManager()->getPlugin()->getLogger()->logException($error);

            if ($player->isConnected()) {
                Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
            }
        });
    }

    /**
     * @param int $guildId
     * @param Closure $onComplete
     *
     * @phpstan-param Closure(Guild|null) : void $onComplete
     */
    public function loadGuildById(int $guildId, Closure $onComplete): void
    {
        Await::f2c(function () use ($guildId): Generator {
            MySQLCredentials::executeSelect("guild.load_guild_base", [
                "guild_id" => $guildId
            ], yield, yield Await::REJECT);

            $rows = yield Await::ONCE;

            if (count($rows) === 0) {
                return null;
            }

            $data = $rows[0];

            $guild = new Guild($this, $data["guild_id"], $data["guild_name"], $data["leader"], $data['max_size'], $data['motd'], $data['xp'], $data['tag'], $data['disabled'] === 1);

            MySQLCredentials::executeSelect("guild.load_guild_members", [
                "guild_id" => $guildId
            ], yield, yield Await::REJECT);

            $rows = yield Await::ONCE;

            foreach ($rows as ['player' => $player, 'role' => $role]) {
                $guild->addMember($player, $role);
            }

            return $guild;
        }, $onComplete, function (Throwable $error) use ($onComplete) {
            $this->getSocialManager()->getPlugin()->getLogger()->logException($error);

            $onComplete(null);
        });
    }

    public function addGuildMember(Player $player, Guild $guild, callable $onComplete): void
    {
        Await::f2c(function () use ($player, $guild): Generator {
            if (count($guild->getMembers()) >= $guild->getMaxGuildSize()) {
                $player->sendMessage(TextFormat::RED . 'That guild is currently full.');
            } else {
                $guild->addMember($player, Guild::RANK_MEMBER, true, yield);
                $result = yield Await::ONCE;

                if (!$player->isConnected()) {
                    return;
                }

                switch ($result) {
                    case Guild::ADD_MEMBER_LOCKED:
                        $player->sendMessage(TextFormat::RED . 'The guild cannot accept any invitations now, try again later.');
                        return;
                    case Guild::ADD_MEMBER_FULL:
                        $player->sendMessage(TextFormat::RED . 'That guild is currently full.');
                        break;
                    case Guild::ADD_MEMBER_EXISTS:
                        $player->sendMessage(TextFormat::RED . 'You are already in this guild!');
                        break;
                    case Guild::ADD_MEMBER_OK:
                        if (!isset($this->reference[$guild->getGuildId()])) {
                            // The guild was de-referenced, well lets load the guild back into memory shall we?
                            $this->loadGuildByPlayer($player, yield);
                            $guild = yield Await::ONCE;

                            // This can be an indicator that the player is offline.
                            if ($guild === null) {
                                return;
                            }
                        } else {
                            $this->reference[$guild->getGuildId()][$player->getName()] = true;
                        }

                        $player->sendMessage(TextFormat::GOLD . 'Welcome to the ' . TextFormat::AQUA . $guild->getGuildName() . TextFormat::GOLD . ' guild!');
                        break;
                    default:
                        $player->sendMessage(TextFormat::RED . 'An unexpected error occurred. Please try again later.');
                        break;
                }

                MySQLCredentials::executeChange("guild_invites.remove_invites", ['invitee_xuid' => $player->getXuid()]);
            }
        }, $onComplete, function (Throwable $error) use ($onComplete) {
            $this->getSocialManager()->getPlugin()->getLogger()->logException($error);

            $onComplete();
        });
    }

    // ---------------------------------------- KAFKA INTERNALS ----------------------------------------

    private function registerKafkaTopic(NGEssentials $plugin): void
    {
        $plugin->getConsumer()?->addTopic(self::GUILD_TOPIC, function (Message $message) use ($plugin): void {
            $payload = json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR);
            if ($payload === null) {
                $plugin->getLogger()->warning("Failed to decode message payload: " . $message->payload);
                return;
            }

            [$guildIdString, $updateTypeString] = explode(":", $message->key);
            $guildId = (int)$guildIdString;
            $updateType = (int)$updateTypeString;

            if (($guild = $this->getGuild($guildId)) === null) {
                return;
            }

            switch ($updateType) {
                case GuildChannel::EVENT_GUILD_DISBAND:
                    foreach ($guild->getMembers() as $member) {
                        $target = NGEssentials::getInstance()->getPlayerManager()->getBestMatchingPlayer($member);
                        if ($target instanceof Player) {
                            $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                            $playerData->setValue($target, PlayerData::GUILD, 0);

                            $this->collectGarbage($target, $guild);
                        }
                    }
                    break;
                case GuildChannel::EVENT_CHANGE_LEADER:
                    $guild->setLeader($payload[0]);
                    break;
                case GuildChannel::EVENT_UPDATE_PLAYER_NAME:
                    [$oldName, $playerName] = $payload;

                    $guild->updatePlayerName($playerName, $oldName);
                    break;
                case GuildChannel::EVENT_CHANGE_ROLES:
                    [$member, $role] = $payload;

                    if ($guild->isMember($member)) {
                        $guild->setMemberRole($member, $role);
                    } else {
                        $guild->addMember($member, $role);
                    }
                    break;
                case GuildChannel::EVENT_CHANGE_GUILD_NAME:
                    $guild->renameGuildName($payload[0]);
                    break;
                case GuildChannel::EVENT_CHANGE_TAG:
                    $guild->setTag($payload[0]);
                    break;
                case GuildChannel::EVENT_ADD_XP:
                    $guild->addXp($payload[0], false);
                    break;
                case GuildChannel::EVENT_CHANGE_DISABLE:
                    $guild->setDisabled($payload[0]);
                    break;
                case GuildChannel::EVENT_CHANGE_MOTD:
                    $guild->setMotd($payload[0]);
                    break;
                default:
                    $plugin->getLogger()->warning("Received message for unknown channel: " . $message->key);
                    break;
            }
        });
    }

    /**
     * Attempt to load the guild for the given player
     *
     * @param Player $player
     * @param Closure|null $onComplete
     * @return void
     */
    public function loadGuildByPlayer(Player $player, ?Closure $onComplete = null): void
    {
        Await::f2c(function () use ($player): Generator {
            MySQLCredentials::executeSelect("guild.search_player_guild", [
                "player" => $player->getName()
            ], yield, yield Await::REJECT);

            $rows = yield Await::ONCE;
            $guild = null;

            $hasGuilds = count($rows) > 0;
            if ($hasGuilds && !isset($this->guilds[$rows[0]['guild_id']])) {
                $this->loadGuildById($rows[0]['guild_id'], yield);

                /** @var Guild $guild */
                $guild = yield Await::ONCE;
            } else if ($hasGuilds) {
                $guild = $this->guilds[$rows[0]['guild_id']];
            }

            if ($guild !== null && $player->isConnected()) {
                $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                $playerData->setValue($player, PlayerData::GUILD, $guild->getGuildId());

                if (!isset($this->guilds[$guild->getGuildId()])) {
                    $this->guilds[$guild->getGuildId()] = $guild;
                }

                $this->reference[$guild->getGuildId()][$player->getName()] = true;

                $defaultWorld = $player->getServer()->getWorldManager()->getDefaultWorld();
                if ($player->getWorld() === $defaultWorld) {
                    $player->setNameTag($this->getSocialManager()->getPlugin()->getPlayerManager()->getNameTag($player));
                }

                return $guild;
            }

            return null;
        }, $onComplete, function (Throwable $error) use ($onComplete) {
            $this->getSocialManager()->getPlugin()->getLogger()->logException($error);

            if ($onComplete !== null) {
                $onComplete(null);
            }
        });
    }

    public function removeInvite(string $invited, int $guildId): void
    {
        NGPlayer::getXuidByName($invited, function ($xuid) use ($guildId) {
            MySQLCredentials::executeInsert("guild_invites.remove_invite", ['guild_id' => $guildId, 'invitee_xuid' => $xuid]);
        });
    }
}