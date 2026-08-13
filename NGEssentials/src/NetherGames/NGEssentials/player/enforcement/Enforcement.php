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
 * @author k3ithos, matcracker, driesboy, sylvrs
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\enforcement;

use libasynCurl\Curl;
use libDiscord\DiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Field;
use libDiscord\message\embed\Footer;
use libDiscord\message\embed\MessageEmbed;
use libforms\elements\Button;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\elements\Toggle;
use libforms\FormManager;
use libReplay\Replays;
use NetherGames\NGEssentials\commands\PingCommand;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\ChatText;
use NetherGames\NGEssentials\player\chat\types\ModerationChat;
use NetherGames\NGEssentials\player\enforcement\objects\StaffPortalInfo;
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\social\PlayerSocialInfo;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\player\utils\PlayerBaseClass;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\tasks\ClosestPlayerTask;
use NetherGames\NGEssentials\utils\discord\DiscordUtils;
use NetherGames\NGEssentials\utils\discord\EmbedColors;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\GameMode;
use pocketmine\player\IPlayer;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;
use function array_diff;
use function array_filter;
use function count;
use function date;

class Enforcement extends PlayerBaseClass
{
    public const ENFORCEMENT_WEBHOOK_ID = '';

    public static ?DiscordChannel $ENFORCEMENT_CHANNEL = null;

    /** @var Reports */
    private Reports $reports;

    public const PORTAL_INFO_ERROR = 0;
    public const PORTAL_INFO_NOT_FOUND = 1;

    public function __construct(PlayerManager $manager)
    {
        parent::__construct($manager);

        $plugin = $manager->getPlugin();
        $serverManager = $plugin->getServerManager();

        if (($replay = $serverManager->getServerType() === ServerManager::REPLAY)) {
            new ReplayManager($this);
        }

        $this->reports = new Reports($this);

        if ($serverManager->enableReplayServer()) {
            new Replays($plugin, !$replay, $replay);
        }

        $plugin->getServer()->getPluginManager()->registerEvents(new EnforcementListener($this), $plugin);
    }

    /**
     * @param Player $player
     * @param IPlayer|StaffPortalInfo $portalInfo
     */
    public function sendPlayerEditor(Player $player, StaffPortalInfo|IPlayer $portalInfo, ?callable $onBack = null): void
    {
        $callable = function (Player $player, StaffPortalInfo $portalInfo) use ($onBack) {
            $form = FormManager::createSimpleForm($player);

            if ($form !== null) {
                $goBack = function (Player $player) use ($portalInfo, $onBack): void {
                    $this->sendPlayerEditor($player, $portalInfo, $onBack);
                };
                $form->setBackClosure($onBack);

                $form->addButton(new ImageButton('Info', ImageButton::IMAGE_TYPE_FACE, $portalInfo->getPlayer()->getName(), function (Player $player) use ($portalInfo, $goBack) {
                    $this->sendInfo($player, $portalInfo, $goBack);
                }));
                $form->addButton(new Button('Recent matches', function (Player $player) use ($portalInfo, $goBack) {
                    ReplayManager::sendRecentMatches($player, $goBack, $portalInfo->getPlayer()->getName());
                }));
                $form->addButton(new Button(TextFormat::RED . 'Punish', function (Player $player) use ($portalInfo, $goBack) {
                    $this->sendPunishmentMenu($player, $portalInfo, $goBack);
                }));
                $form->addButton(new Button(TextFormat::RED . 'Manage punishments', function (Player $player) use ($portalInfo, $goBack) {
                    $this->sendPunishmentsViewer($player, $portalInfo, $goBack);
                }));
                $form->addButton(new Button('View statistics', function (Player $player) use ($portalInfo, $goBack) {
                    Forms::sendStats($player, $portalInfo->getPlayer()->getName(), $goBack);
                }));

                $playerName = $portalInfo->getPlayer()->getName();
                if ($portalInfo->getPlayer() instanceof NGPlayer) {
                    $form->setTitle('§8[§2ONLINE§8] ' . TextFormat::RESET . $playerName);
                    if ($player->hasPermission(Permissions::RANK_CREW)) {
                        $form->addButton(new Button(TextFormat::RED . 'Kick', function (Player $player) use ($portalInfo, $goBack) {
                            $this->sendKickMenu($player, $portalInfo, $goBack);
                        }));
                    }
                } elseif ($portalInfo->getServer() === null) {
                    $form->setTitle('§8[§cOFFLINE§8] ' . TextFormat::RESET . $playerName);
                } else {
                    $form->setTitle('§8[§2ONLINE§8] ' . TextFormat::RESET . $playerName);
                    if ($player->hasPermission(Permissions::RANK_TRAINEE)) {
                        $form->addButton(new Button('Transfer to server', function (Player $player) use ($portalInfo) {
                            $playerManager = $this->getManager();
                            $plugin = $playerManager->getPlugin();
                            $server = $portalInfo->getServer();
                            $serverType = $server->getCluster()->getServerType();

                            if ($serverType === ServerManager::REPLAY) {
                                $player->sendMessage(TextFormat::RED . "You can't teleport to someone watching a replay.");
                            } elseif ($serverType === ServerManager::SETUP) {
                                $player->sendMessage(TextFormat::RED . "You can't teleport to someone in a setup server.");
                            } else {
                                $plugin->getPlayerData()->setValue($player, PlayerData::TRACK, $portalInfo->getPlayer()->getName());
                                $playerManager->transferPlayer($player, $server, '', true);
                            }
                        }));
                    }
                    if ($player->hasPermission(Permissions::RANK_CREW)) {
                        $form->addButton(new Button(TextFormat::RED . 'Kick', function (Player $player) use ($portalInfo, $goBack) {
                            $this->sendKickMenu($player, $portalInfo, $goBack);
                        }));
                    }
                }

                $form->sendForm();
            }
        };


        if ($portalInfo instanceof IPlayer) {
            $this->getPortalInfo($portalInfo, function ($portalInfo) use ($player, $callable): void {
                if ($player->isConnected()) {
                    if ($portalInfo instanceof StaffPortalInfo) {
                        $callable($player, $portalInfo);
                    } elseif ($portalInfo === Enforcement::PORTAL_INFO_ERROR) {
                        $player->sendMessage('§cCould not load player information');
                    } elseif ($portalInfo === Enforcement::PORTAL_INFO_NOT_FOUND) {
                        $player->sendMessage('§cSorry, that player could not be found.');
                    }
                }
            });
        } else {
            $callable($player, $portalInfo);
        }
    }

    public function sendInfo(Player $player, StaffPortalInfo $portalInfo, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Player Info: ' . $portalInfo->getPlayer()->getName());

            $p = $portalInfo->getPlayer();
            $playerData = $this->getPlugin()->getPlayerData();

            if ($p instanceof NGPlayer && $p->isConnected()) {
                $form->addElement(new Label('§aName: §f' . $this->getManager()->getPlayerColouredName($p, TextFormat::YELLOW, true)));
                $form->addElement(new Label('§aStatus: §f§2ONLINE'));

                if ($playerData->getBool($p, PlayerData::NICK)) {
                    $form->addElement(new Label('§aNickname: §f' . $playerData->getString($p, PlayerData::NICK)));
                }

                [$downstream, $upstream] = $p->getLatencyData();
                $form->addElement(new Label('§aTotal Ping: §f' . PingCommand::parseColoredPing($downstream + $upstream)));
                $form->addElement(new Label('§aUpstream Ping: §f' . PingCommand::parseColoredPing($upstream)));
                $form->addElement(new Label('§aDownstream Ping: §f' . PingCommand::parseColoredPing($downstream)));

                $form->addElement(new Label('§aOS: §f' . $p->getDeviceOS()));
                $form->addElement(new Label('§aModel: §f' . $p->getDeviceModel()));
                $form->addElement(new Label('§aControls: §f' . $p->getInputName()));
                $form->addElement(new Label('§aProxy: §f' . ($p->getProxyId() ?? 'None')));

                if ($player->hasPermission(Permissions::RANK_OWNER)) {
                    $form->addElement(new Label('§aIP: §f' . $p->getNetworkSession()->getIp()));
                    $form->addElement(new Label('§aXUID: §f' . $p->getXuid()));
                    $form->addElement(new Label('§aDevice ID: §f' . $p->getDeviceId()));
                }
            } else {
                $form->addElement(new Label('§aName: §f' . $portalInfo->getPlayer()->getName()));
                if (($server = $portalInfo->getServer()) === null) {
                    $form->addElement(new Label('§aStatus: §f§cOFFLINE'));
                } else {
                    $form->addElement(new Label('§aStatus: §f§2ONLINE'));
                    $form->addElement(new Label('§aPlaying on ' . $server->getCluster()->getName()));

                    if (($proxy = $portalInfo->getProxy()) !== '') {
                        $form->addElement(new Label('§aProxy: §f' . $proxy));
                    }
                }

                if ($player->hasPermission(Permissions::RANK_OWNER)) {
                    $form->addElement(new Label('§aIP: §f' . $portalInfo->getIp()));
                    $form->addElement(new Label('§aXUID: §f' . $portalInfo->getXuid()));
                    $form->addElement(new Label('§aDevice ID: §f' . $portalInfo->getDeviceId()));
                }
                $form->addElement(new Label('§6For device specific information (OS, model, controls, etc), ask the player to join this server.'));
            }

            $form->sendForm();
        }
    }


    public function sendPunishmentMenu(Player $player, StaffPortalInfo $portalInfo, ?callable $onBack = null): void
    {
        $player->sendMessage(TextFormat::RED . "The warning/punishment system is currently unavailable.");
        if ($onBack !== null) {
            $onBack($player);
        }
    }


    public function kickPlayer(string $playerName, string $playerXuid, string $reason, Player|string $issuer, callable $onFailure, bool $notifyKick = true): void
    {
        Utils::validateCallableSignature(function (string $reason): void {}, $onFailure);

        $issuerName = $issuer instanceof Player ? $issuer->getName() : $issuer;

        $target = $this->getManager()->getPlugin()->getServer()->getPlayerExact($playerName);
        if ($target !== null) {
            $target->kick($reason);
            if ($notifyKick) {
                $this->sendModerationMessage('§b' . $playerName . ' §ahas been kicked by §b' . $issuerName . '§a for §6' . $reason);
            }
        } else {
            $onFailure("Player not found online on this server.");
        }
    }

    public function punishPlayer(string $playerName, string $playerXuid, string $reason, Player|string $issuer, callable $onFailure): void
    {
        Utils::validateCallableSignature(function (string $reason): void {}, $onFailure);

        $onFailure("This feature is currently unavailable.");
    }

    public function sendModerationMessage(string $message, string $prefix = ModerationChat::PREFIX): void
    {
        $text = new ChatText(new RawMessage($prefix . TextFormat::RESET . $message));
        $this->getManager()->getChatManager()->getGlobalChatManager()->sendModerationMessage($text);
    }

    public function sendPunishmentsViewer(Player $player, ?StaffPortalInfo $portalInfo, ?callable $onBack): void
    {
        $player->sendMessage(TextFormat::RED . "Viewing punishments is currently unavailable.");
        if ($onBack !== null) {
            $onBack($player);
        }
    }

    public function sendCategoryViewer(Player $player, string $xuid, string $categoryName, array $punishments, ?StaffPortalInfo $portalInfo, ?callable $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Punishments' . ($portalInfo === null ? '' : ' for ' . $portalInfo->getPlayer()->getName()));
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($xuid, $categoryName, $punishments, $portalInfo, $onBack) {
                $this->sendCategoryViewer($player, $xuid, $categoryName, $punishments, $portalInfo, $onBack);
            };

            foreach ($punishments as $punishmentData) {
                $isTraced = $punishmentData['xuid'] !== $xuid;
                $form->addButton(new Button($punishmentData['reason']['name'] . ' at ' . date('M d Y', $punishmentData['issuedAt']) . ($isTraced ? TextFormat::EOL . TextFormat::RED . "Detected by tracing" : ""), function (Player $player) use ($xuid, $portalInfo, $punishmentData, $goBack) {
                    if ($portalInfo === null) {
                        $this->sendPunishmentViewer($player, $punishmentData, $goBack);
                    } else {
                        $this->sendPunishmentEditor($player, $xuid, $punishmentData, $portalInfo, $goBack);
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public function sendPunishmentViewer(Player $player, array $punishmentData, ?callable $onBack): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $form->setTitle('Warnings');

            $form->setContent(
                '§aUnique ID: §f' . $punishmentData['id'] . TextFormat::EOL .
                '§aTime: §f' . date('M d Y G:i:s', $punishmentData['issuedAt']) . ' (UTC)' . TextFormat::EOL .
                '§aReason: §f' . $punishmentData['reason']['name']
            );

            $form->setButton1(new Button(TextFormat::RED . 'Exit'));
            $form->setButton2(new Button(TextFormat::RED . TextFormat::BOLD . 'Go back', $onBack));

            $form->sendForm();
        }
    }

    public function sendPunishmentEditor(Player $player, string $targetXuid, array $punishmentData, StaffPortalInfo $portalInfo, ?callable $onBack): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            Await::f2c(function () use ($targetXuid, $punishmentData, $portalInfo, $onBack, $form) {
                $isTraced = $punishmentData['xuid'] !== $targetXuid;

                NGPlayer::getNameByXuid($punishmentData['issuedBy'], yield);
                NGPlayer::getNameByXuid($punishmentData['xuid'], yield);

                [$issuer, $offender] = yield Await::ALL;

                $form->setTitle('Warnings: ' . $offender);
                $form->setContent(
                    '§aUnique ID: §f' . $punishmentData['id'] . TextFormat::EOL .
                    '§aIssued by: §f' . $issuer . TextFormat::EOL .
                    '§aTime: §f' . date('M d Y G:i:s', $punishmentData['issuedAt']) . ' (UTC)' . TextFormat::EOL .
                    '§aReason: §f' . $punishmentData['reason']['name']
                );

                $form->setButton1(new Button(TextFormat::RED . TextFormat::BOLD . ($isTraced ? 'Whitelist' : 'Delete'), function (Player $player) use ($isTraced, $targetXuid, $portalInfo, $punishmentData): void {
                    if ($player->hasPermission(Permissions::RANK_ADMIN) || $punishmentData['issuedBy'] === $player->getXuid()) {
                        $playerName = $portalInfo->getPlayer()->getName();
                        $reason = $punishmentData['reason']['name'];

                        Curl::deleteRequest('http://127.0.0.1/punishment/' . $punishmentData['id'] . "?issuer=" . $player->getXuid() . ($isTraced ? "&tracedXuid=$targetXuid" : ""), [], 2, [], function (?InternetRequestResult $result) use ($isTraced, $player, $playerName, $reason): void {
                            if ($player->isConnected()) {
                                if ($result === null) {
                                    $player->sendMessage(TextFormat::RED . 'An error occurred while deleting punishment.');
                                } else if ($result->getCode() !== 200) {
                                    $player->sendMessage(TextFormat::RED . 'An error occurred while deleting the punishment: ' . $result->getCode() . ' - ' . $result->getBody());
                                }
                            }

                            $this->sendModerationMessage('§b' . $player->getName() . ' §ahas ' . ($isTraced ? 'whitelisted' : 'deleted') . ' a warning from §b' . $playerName . '§a for §6' . $reason);

                            $embed = MessageEmbed::rich("Warning - " . ($isTraced ? "Whitelist" : "Removal"))
                                ->setColor(EmbedColors::ALERT)
                                ->addFields(
                                    Field::simple("Player", $playerName),
                                    Field::simple("Reason", $reason),
                                )
                                ->setThumbnail(
                                    DiscordUtils::asThumbnail($playerName)
                                )
                                ->setFooter(Footer::simple(
                                    ($isTraced ? "Whitelisted" : "Removed") . " by: {$player->getName()}",
                                    DiscordUtils::getAvatar($player->getName())
                                ));
                            self::$ENFORCEMENT_CHANNEL?->post(DiscordMessage::embed($embed));
                        });
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You do not have permission to ' . ($isTraced ? 'whitelist' : 'remove') . ' issued by other staff.');
                    }
                }));
                $form->setButton2(new Button(TextFormat::RED . 'Go back', $onBack));

                $form->sendForm();
            });
        }
    }

    public function sendKickMenu(Player $player, StaffPortalInfo $portalInfo, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $playerName = $portalInfo->getPlayer()->getName();
            $form->setTitle("Kick " . $playerName);

            $form->addElement(new Input("Reason", "Enter a reason for the kick", "", function (Player $player, string $reason) use ($portalInfo, $playerName): void {
                $this->kickPlayer($playerName, $portalInfo->getXuid(), $reason, $player, function (string $reason) use ($player): void {
                    if ($player->isConnected()) {
                        $player->sendMessage(TextFormat::RED . $reason);
                    }
                });
            }));

            $form->sendForm();
        }
    }

    public function sendReportList(Player $player, ?callable $onBack = null): void
    {
        $player->sendMessage(TextFormat::RED . "The report list system is currently unavailable.");
        if ($onBack !== null) {
            $onBack($player);
        }
    }

    public function sendPlayerReportList(Player $player, string $playerName, array $report, ?callable $onBack = null): void
    {
        if (($form = FormManager::createSimpleForm($player)) === null) {
            return;
        }

        $goBack = function (Player $player) use ($playerName, $report, $onBack): void {
            $this->sendPlayerReportList($player, $playerName, $report, $onBack);
        };

        $matches = array_filter($report['matchesReported'], fn($report) => $report != null);
        $totalReports = count($report['playersReported']);
        $reportsVisual = $totalReports > 15 ? TextFormat::RED . $totalReports : ($totalReports > 5 ? TextFormat::YELLOW . $totalReports : TextFormat::DARK_GRAY . $totalReports);

        $content =
            TextFormat::GRAY . "Total reports: " . $reportsVisual . TextFormat::EOL .
            TextFormat::GRAY . "Replays available: " . TextFormat::GOLD . count($matches) . TextFormat::RESET . TextFormat::EOL;

        foreach ($report['reportHit'] as $category => $totalHit) {
            $hitVisual = $totalHit > 15 ? TextFormat::RED . $totalHit : ($totalHit > 5 ? TextFormat::YELLOW . $totalHit : TextFormat::DARK_GRAY . $totalHit);
            $content .= TextFormat::EOL . TextFormat::GRAY . "$category: $hitVisual" . TextFormat::RESET;
        }

        $form->setTitle($playerName . " Reports.");
        $form->setContent($content);

        $form->addButton(new Button("Open Staff Portal", function (Player $player) use ($goBack, $playerName): void {
            $this->sendPlayerEditor($player, $this->getPlugin()->getPlayerManager()->getBestMatchingPlayer($playerName), $goBack);
        }));
        $form->addButton(new Button("Open Replay", function (Player $player) use ($goBack, $matches, $playerName): void {
            ReplayManager::sendRecentMatchesByIds($player, $matches, $goBack, $playerName);
        }));
        $form->addButton(new Button(TextFormat::RED . "Delete Report", static function (Player $player) use ($report, $playerName): void {
            Curl::deleteRequest("http://127.0.0.1/report/" . $report['player'] . "?resolution=INSUFFICIENT", [], closure: function (?InternetRequestResult $result) use ($player, $playerName): void {
                if (!$player->isConnected()) {
                    return;
                }

                if ($result !== null && $result->getCode() === 200) {
                    $player->sendMessage(TextFormat::RED . "The report for $playerName has been removed.");
                } else {
                    Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
                }
            });
        }));

        $form->sendForm();
    }

    public function sendStaffList(Player $player, ?callable $onBack = null): void
    {
        $player->sendMessage(TextFormat::RED . "The staff directory system is currently unavailable.");
        if ($onBack !== null) {
            $onBack($player);
        }
    }

    public function sendStaffPortal(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Staff Portal');

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendStaffPortal($player, $onBack);
            };

            $form->addButton(new Button('Search Player', function (Player $player) use ($goBack): void {
                $this->sendPlayerSelector($player, $goBack);
            }));
            $form->addButton(new Button('Player Reports', function (Player $player) use ($goBack): void {
                $this->sendReportList($player, $goBack);
            }));
            $form->addButton(new Button('Staff List', function (Player $player) use ($goBack): void {
                $this->sendStaffList($player, $goBack);
            }));
            $form->addButton(new Button('Settings', function (Player $player) use ($goBack): void {
                $form = FormManager::createCustomForm($player, $goBack);

                if ($form !== null) {
                    $form->setTitle('Settings');

                    $playerData = $this->getPlugin()->getPlayerData();
                    $form->addElement(new Toggle('Staff Notifications', $playerData->getBool($player, PlayerData::STAFF_NOTIFICATIONS), function (Player $player, bool $value) use ($playerData) {
                        $playerData->setValue($player, PlayerData::STAFF_NOTIFICATIONS, $value);

                        if ($value) {
                            $player->sendMessage(TextFormat::GREEN . 'Enabled staff notifications. You will now be notified of staff chats.');
                        } else {
                            $player->sendMessage(TextFormat::RED . 'Disabled staff notifications. You will no longer be notified of staff chats.');
                        }
                    }));

                    if ($player->hasPermission(Permissions::RANK_TRAINEE)) {
                        $form->addElement(new Toggle('Chat Relay', $playerData->getBool($player, PlayerData::CHAT_RELAY), function (Player $player, bool $value) use ($playerData) {
                            $playerData->setValue($player, PlayerData::CHAT_RELAY, $value);

                            if ($value) {
                                $player->sendMessage(TextFormat::GREEN . 'Enabled chat relay. You will now see all chat in the server.');
                            } else {
                                $player->sendMessage(TextFormat::RED . 'Disabled chat relay. You will no longer see all chat in the server.');
                            }
                        }));

                        $form->addElement(new Toggle('Reports', $playerData->getBool($player, PlayerData::REPORTS), function (Player $player, bool $value) use ($playerData) {
                            $playerData->setValue($player, PlayerData::REPORTS, $value);

                            if ($value) {
                                $player->sendMessage(TextFormat::GREEN . 'Enabled reports. You will now be notified of reports submitted by players or the anticheat.');
                            } else {
                                $player->sendMessage(TextFormat::RED . 'Disabled reports. You will no longer be notified of reports submitted by players or the anticheat.');
                            }
                        }));
                    }

                    if ($player->hasPermission(Permissions::RANK_OWNER)) {
                        $form->addElement(new Toggle('Whisper Relay', $playerData->getBool($player, PlayerData::WHISPER_RELAY), function (Player $player, bool $value) use ($playerData) {
                            $playerData->setValue($player, PlayerData::WHISPER_RELAY, $value);

                            if ($value) {
                                $player->sendMessage(TextFormat::GREEN . 'Enabled whisper relay. You will now see all direct messages in the server.');
                            } else {
                                $player->sendMessage(TextFormat::RED . 'Disabled whisper relay. You will no longer see all direct messages in the server.');
                            }
                        }));
                    }

                    $form->sendForm();
                }
            }));

            $form->sendForm();
        }
    }

    public function sendPlayerSelector(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Search Menu');

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendPlayerSelector($player, $onBack);
            };

            $players = $this->getManager()->getPlayerNames(array_diff($player->getServer()->getOnlinePlayers(), [$player]));
            $form->addElement(new Dropdown('Players online right now:', $players, -1, function (Player $player, int $value) use ($players, $goBack) {
                $this->sendPlayerEditor($player, $player->getServer()->getOfflinePlayer($players[$value]), $goBack);
            }));
            $form->addElement(new Input("Didn't find them? Type their name:", 'Steve', '', function (Player $player, string $value) use ($goBack): void {
                $this->sendPlayerEditor($player, $player->getServer()->getOfflinePlayer(TextFormat::clean($value)), $goBack);
            }));

            $form->sendForm();
        }
    }

    public function getPortalInfo(IPlayer $p, callable $callable): void
    {
        if ($p instanceof NGPlayer) {
            $callable(new StaffPortalInfo($p, $p->getNetworkSession()->getIp(), $p->getXuid(), $p->getDeviceId(), $this->getPlugin()->getServerManager()->getServer()));
        } elseif (MySQLCredentials::isDatabaseOnline()) {
            NGPlayer::getXuidByName($p->getName(), function (?string $xuid, ?string $name) use ($callable): void {
                if ($xuid === null || $name === null) {
                    $callable(self::PORTAL_INFO_NOT_FOUND);
                } else {
                    $p = new OfflinePlayer($name, null);

                    SocialManager::requestPlayerInfo($xuid, function (?PlayerSocialInfo $info) use ($p, $xuid, $callable): void {
                        if ($info === null) {
                            $callable(new StaffPortalInfo($p, "UNKNOWN", $xuid, "UNKNOWN", null, ''));
                            return;
                        }

                        $server = $this->getPlugin()->getServerManager()->getServer($info->location);
                        $callable(new StaffPortalInfo($p, $info->address, $xuid, "UNKNOWN", $server, $info->proxyId));
                    });
                }
            });
        } else {
            $callable(self::PORTAL_INFO_ERROR);
        }
    }

    /**
     * @param string $playerName
     * @return Player[]
     */
    public function isTracking(string $playerName): array
    {
        $playerData = $this->getPlugin()->getPlayerData();

        return array_filter($this->getPlugin()->getServer()->getOnlinePlayers(), static function (Player $player) use ($playerData, $playerName) {
            return $playerData->getString($player, PlayerData::TRACK) === $playerName;
        });
    }

    /**
     * @param string $message
     * @param Player[] $exclude
     * @param World|null $excludedWorld
     */
    public function sendRelayMessage(string $message, array $exclude = [], ?World $excludedWorld = null): void
    {
        $playerData = $this->getPlugin()->getPlayerData();

        $staff = array_filter(array_diff($this->getStaff(), $exclude), static function (Player $player) use ($playerData, $excludedWorld) {
            return $playerData->getBool($player, PlayerData::CHAT_RELAY) && $player->getWorld() !== $excludedWorld;
        });

        $this->getPlugin()->getServer()->broadcastMessage($message, $staff);
    }

    /**
     * @return Player[]
     */
    public function getStaff(): array
    {
        return array_filter($this->getPlugin()->getServer()->getOnlinePlayers(), static function (Player $player) {
            return $player->hasPermission(Permissions::RANK_TRAINEE);
        });
    }

    public function canUseTracking(Player $player, ?string $serverType = null): bool
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->isMMOGame($serverType ?? $serverManager->getServerType()) && !$player->hasPermission(Permissions::RANK_TRAINEE)) {
            $player->sendMessage(TextFormat::RED . 'You do not have the permission to track players in this server.');
        } elseif ($serverType === ServerManager::REPLAY) {
            $player->sendMessage(TextFormat::RED . "You can't track someone while they are watching a replay.");
        } elseif ($serverType === ServerManager::SETUP) {
            $player->sendMessage(TextFormat::RED . "You can't teleport to someone in a setup server.");
        } else {
            return true;
        }

        return false;
    }

    public function setTracking(NGPlayer $player, bool|string $value, bool $transfer = true): void
    {
        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();
        $playerData = $plugin->getPlayerData();
        $playerManager = $plugin->getPlayerManager();

        if ($value === false) {
            if ($playerData->getBool($player, PlayerData::TRACK)) {
                $playerData->setValue($player, PlayerData::TRACK, '');
            } else {
                $player->sendMessage('§cYou are not in spectator mode.');
                return;
            }

            $player->teleport($plugin->getServerManager()->getSpawn());
            $player->setHasBlockCollision(true);
            $player->getEffects()->remove(VanillaEffects::NIGHT_VISION());

            if ($serverManager->isMMOGame()) {
                $player->setGamemode(GameMode::SURVIVAL);
            } else {
                $player->setGamemode(GameMode::ADVENTURE);

                if ($serverManager->enableLobbyHandling()) {
                    LobbyItems::setLobbyInventory($player);

                    if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                        $player->setAllowFlight(!$playerData->getBool($player, PlayerData::NICK) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK);
                    }

                    if (($petHandler = $playerManager->getPetsManager()) !== null) {
                        $petHandler->spawnPet($player);
                    }
                } else {
                    $playerManager->transferPlayer($player);
                }
            }

            foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
                $p->getNetworkSession()->onPlayerAdded($player);
            }

            $player->sendMessage('§aDisabled tracking mode and returned to the lobby.');
        } else {
            $p = $playerManager->getBestMatchingPlayer($value);

            if ($p instanceof Player && $p->isConnected()) {
                if (!$this->canUseTracking($player)) {
                    return;
                }

                $playerData->setValue($player, PlayerData::TRACK, $p->getName());
                $this->setupTracking($player, $p);

                if (($petHandler = $playerManager->getPetsManager()) !== null) {
                    $petHandler->removePet($player);
                }

                $player->sendMessage('§6You are now in spectator mode to track §b' . $p->getName() . '§6.');
            } elseif ($transfer) {
                if (MySQLCredentials::isDatabaseOnline()) {
                    NGPlayer::doesNameExist($value, function (bool $exists) use ($player, $value): void {
                        if (!$player->isConnected()) {
                            return;
                        }

                        if ($exists) {
                            SocialManager::requestPlayerInfo($value, function (?PlayerSocialInfo $info) use ($player): void {
                                /** @phpstan-ignore-next-line */
                                if (!$player->isConnected()) {
                                    return;
                                }

                                if ($info === null) {
                                    Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                                    return;
                                }

                                $playerManager = $this->getManager();
                                $plugin = $playerManager->getPlugin();
                                $server = $plugin->getServerManager()->getServer($info->location);

                                if ($server === null) {
                                    $player->sendMessage(TextFormat::RED . 'An unexpected error occurred. Please try again later.');
                                } elseif ($this->canUseTracking($player, $server->getCluster()->getServerType())) {
                                    $player->sendMessage('§6You are now in spectator mode to track §b' . $info->playerName . '§6.');

                                    $plugin->getPlayerData()->setValue($player, PlayerData::TRACK, $info->playerName);
                                    $playerManager->transferPlayer($player, $server, '', true);
                                }
                            });
                        } else {
                            $player->sendMessage('§cSorry, that player could not be found.');
                        }
                    });
                } else {
                    Translator::sendMessage($player, "db.error", Translator::TYPE_ERROR);
                }
            } else {
                $player->sendMessage('§cSorry, that player could not be found.');

                if ($serverManager->enableLobbyHandling()) {
                    $this->setTracking($player, false);
                } else {
                    $playerManager->transferPlayer($player);
                }
            }
        }
    }

    public function setupTracking(Player $player, Player $p): void
    {
        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();

        $player->teleport($p->getPosition());
        $plugin->getServer()->getLogger()->info($player->getName() . " started tracking " . $p->getName());
        $player->setGamemode(GameMode::SPECTATOR);
        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
            $p->getNetworkSession()->onPlayerRemoved($player);
        }
        $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 0, false));

        $staff = Permissions::isStaff($player);
        if (!$serverManager->isMMOGame()) {
            $player->getInventory()->setHeldItemIndex(2);

            if ($staff) {
                $player->getInventory()->setContents([0 => LobbyItems::getStaffPortalItem(), 4 => LobbyItems::getSpectatorCompass(), 6 => LobbyItems::getNoClipToggleItem(), 8 => LobbyItems::getSpectatorBed()]);
            } else {
                $player->getInventory()->setContents([0 => LobbyItems::getNoClipToggleItem(), 4 => LobbyItems::getSpectatorCompass(), 8 => LobbyItems::getSpectatorBed()]);
            }
        }

        if ($staff) {
            $plugin->getScheduler()->scheduleRepeatingTask(new ClosestPlayerTask($player, $plugin), 20);
        }
    }

    public function getReportsHandler(): Reports
    {
        return $this->reports;
    }
}
