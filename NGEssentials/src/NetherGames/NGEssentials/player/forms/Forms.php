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

namespace NetherGames\NGEssentials\player\forms;

use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\elements\Toggle;
use libforms\FormManager;
use libforms\SimpleForm;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\emojis\Emojis;
use NetherGames\NGEssentials\player\enforcement\ReplayManager;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Server;
use NetherGames\NGEssentials\servers\ServersCluster;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function count;

abstract class Forms
{
    public static function sendMinigameSelector(Player $player, NGEssentials $ess): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle(Translator::getTranslationPlayer($player, "forms.teleporter"));
            $form->setType(SimpleForm::FORM_COLUMN_2);

            foreach (ServerManager::BUTTONS as $server => $data) {
                if (isset($data['serverType']) && !isset($data['dummy'])) {
                    $serverType = $data['serverType'];
                    $gameType = '';

                    if (isset($data['gameTypes'])) {
                        $gameTypes = $data['gameTypes'];
                        if (count($gameTypes) > 1) {
                            $status = [
                                Server::OFFLINE => 0,
                                Server::ONLINE => 0,
                                Server::FULL => 0
                            ];

                            $onlinePlayers = 0;

                            foreach ($gameTypes as $gameType) {
                                $cluster = $ess->getServerManager()->getCluster($serverType, $gameType);

                                $onlinePlayers += $cluster->getOnlinePlayers();
                                $status[$cluster->getStatus()]++;
                            }

                            if ($status[Server::OFFLINE] === count($data['gameTypes'])) {
                                $status = Translator::getTranslationPlayer($player, "forms.teleporter.offline", Translator::TYPE_ERROR);
                            } elseif ($status[Server::OFFLINE] > 0) {
                                $status = Translator::getTranslationPlayer($player, "forms.teleporter.partly_online", Translator::TYPE_WARNING);
                            } else {
                                $status = TextFormat::DARK_GREEN . $onlinePlayers . ' playing';
                            }

                            $form->addButton(new ImageButton($server . TextFormat::EOL . $status, ImageButton::IMAGE_TYPE_PATH, $data['icon'], static function (Player $player) use ($ess, $serverType) {
                                self::sendGameTypeSelector($player, $serverType, $ess, function (Player $player) use ($ess) {
                                    self::sendMinigameSelector($player, $ess);
                                });
                            }));
                            continue;
                        }

                        $gameType = $gameTypes[0];
                    }

                    $cluster = $ess->getServerManager()->getCluster($serverType, $gameType);

                    $form->addButton(new ImageButton($server . TextFormat::EOL . $cluster->getStringStatus($player), ImageButton::IMAGE_TYPE_PATH, $data['icon'], static function (Player $player) use ($ess, $cluster) {
                        if ($cluster instanceof ServersCluster) {
                            self::sendServerSelector($player, $cluster, $ess, static function (Player $player) use ($ess) {
                                self::sendMinigameSelector($player, $ess);
                            });
                        } else {
                            $ess->getPlayerManager()->transferPlayer($player, $cluster);
                        }
                    }));
                } elseif (isset($data['teleport'])) {
                    if ($server === 'Arcade') {
                        $status = [
                            Server::OFFLINE => 0,
                            Server::ONLINE => 0,
                            Server::FULL => 0
                        ];

                        $onlinePlayers = 0;

                        foreach ([ServerManager::SC/*, ServerManager::SP*/, ServerManager::MS] as $serverType) {
                            $server2 = ServerManager::BUTTONS[ServerManager::getName($serverType)];

                            if (isset($server2['gameTypes'])) {
                                $gameTypes = $server2['gameTypes'];

                                foreach ($gameTypes as $gameType) {
                                    $cluster = $ess->getServerManager()->getCluster($serverType, $gameType);

                                    $onlinePlayers += $cluster->getOnlinePlayers();

                                    $status[$cluster->getStatus()]++;
                                }
                            } else {
                                $cluster = $ess->getServerManager()->getCluster($serverType);

                                $onlinePlayers += $cluster->getOnlinePlayers();

                                $status[$cluster->getStatus()]++;
                            }
                        }

                        if ($status[Server::OFFLINE] > 0) {
                            $status = Translator::getTranslationPlayer($player, "forms.teleporter.partly_online", Translator::TYPE_WARNING);
                        } else {
                            $status = TextFormat::DARK_GREEN . $onlinePlayers . ' playing';
                        }

                        $form->addButton(new ImageButton($server . TextFormat::EOL . $status, ImageButton::IMAGE_TYPE_PATH, $data['icon'], static function (Player $player) use ($ess, $data) {
                            $player->teleport($ess->getServerManager()->getSpawn($data['teleport']));
                        }));
                    } else {
                        $form->addButton(new ImageButton($server, ImageButton::IMAGE_TYPE_PATH, $data['icon'], static function (Player $player) use ($ess, $data) {
                            $player->teleport($ess->getServerManager()->getSpawn($data['teleport']));
                        }));
                    }
                }
            }

            $form->sendForm();
        }
    }

    public static function sendGameTypeSelector(Player $player, string $serverType, NGEssentials $ess, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $server = ServerManager::BUTTONS[ServerManager::getName($serverType)];

            if (($gameTypesCount = count($server['gameTypes'])) < 5) {
                $form->setType(SimpleForm::getDynamicType($gameTypesCount));
            }
            $form->setTitle(Translator::getTranslationPlayer($player, "forms.teleporter"));
            $form->setBackClosure($onBack);

            foreach ($server['gameTypes'] as $gameType) {
                $cluster = $ess->getServerManager()->getCluster($serverType, $gameType);

                $form->addButton(new ImageButton(TextFormat::GOLD . $gameType . TextFormat::EOL . $cluster->getStringStatus($player), ImageButton::IMAGE_TYPE_PATH, $server['icon'], static function (Player $player) use ($ess, $cluster, $serverType, $gameType) {
                    if ($cluster instanceof ServersCluster && count($cluster->getServers()) > 1) {
                        self::sendGameRegionSelector($player, $cluster, $ess);
                    } else {
                        $ess->getPlayerManager()->transferPlayer($player, $serverType, $gameType);
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendGameRegionSelector(Player $player, ServersCluster $cluster, NGEssentials $ess): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $serverData = ServerManager::BUTTONS[ServerManager::getName($cluster->getServerType())];

            $form->setTitle(Translator::getTranslationPlayer($player, "forms.teleporter"));
            $form->setType(SimpleForm::getDynamicType(count($servers = $cluster->getServers())));
            $form->setContent('Select a region:');

            foreach ($servers as $server) {
                $form->addButton(new ImageButton(TextFormat::GOLD . ServerManager::REGION_TO_NAME[$server->getRegion()] . TextFormat::EOL . $cluster->getStringStatus($player, $server), ImageButton::IMAGE_TYPE_PATH, $serverData['icon'], static function (Player $player) use ($ess, $server) {
                    $ess->getPlayerManager()->transferPlayer($player, $server);
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendServerSelector(Player $player, ServersCluster $cluster, NGEssentials $ess, ?callable $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $serverManager = $ess->getServerManager();

            $form->setTitle('Server Selector');
            $form->setBackClosure($onBack);

            foreach (array_values($cluster->getServers()) as $serverId => $server) {
                $isCurrent = $server->getUniqueId() === $serverManager->getUniqueId();

                $addon = $isCurrent ? TextFormat::EOL . TextFormat::YELLOW . 'You are on this server' : '';
                $teleport = $isCurrent ? $cluster : $server;

                $form->addButton(new Button($server->getCluster()->getName() . ' ' . ($serverId + 1) . TextFormat::GRAY . ' - ' . $cluster->getStringStatus($player, $server) . $addon, static function (Player $player) use ($teleport, $ess, $onBack) {
                    if ($teleport instanceof ServersCluster) {
                        self::sendServerSelector($player, $teleport, $ess, $onBack);
                    } else {
                        $ess->getPlayerManager()->transferPlayer($player, $teleport, '');
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendTrackMenu(Player $player): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $playerManager = NGEssentials::getInstance()->getPlayerManager();
            $ingamePlayers = $playerManager->getPlayerNames(array_diff($player->getWorld()->getPlayers(), [$player]));

            $form->setTitle('Track Menu');
            $form->setContent('Select a player:');

            foreach ($ingamePlayers as $ingamePlayer) {
                $form->addButton(new ImageButton('Teleport to ' . $ingamePlayer, ImageButton::IMAGE_TYPE_FACE, $ingamePlayer, static function (Player $player) use ($ingamePlayer) {
                    if (($ingamePlayer = $player->getServer()->getPlayerExact($ingamePlayer)) !== null) {
                        $playerData = NGEssentials::getInstance()->getPlayerData();

                        if ($playerData->getBool($player, PlayerData::TRACK)) {
                            $player->teleport($ingamePlayer->getLocation());
                            $playerData->setValue($player, PlayerData::TRACK, $ingamePlayer->getName());
                        } else {
                            $player->sendMessage('§cYou are not in tracking mode.');
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . 'That player is not in this server anymore.');
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendSettings(Player $player, NGEssentials $ess): void
    {
        /** @var NGPlayer $player */
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $goBack = static function (Player $player) use ($ess) {
                self::sendSettings($player, $ess);
            };

            $form->setTitle('Settings');

            $form->addButton(new Button('Preferences', static function (Player $player) use ($ess, $goBack) {
                $form = FormManager::createCustomForm($player, $goBack);

                if ($form !== null) {
                    $form->setTitle('Preferences');

                    $playerData = $ess->getPlayerData();
                    $form->addElement(new Toggle(Translator::getTranslationPlayer($player, "forms.settings.hideplayers"), $playerData->getBool($player, PlayerData::HIDE_PLAYERS), function (Player $player, bool $value) use ($ess, $playerData) {
                        $playerData->setValue($player, PlayerData::HIDE_PLAYERS, $value);
                        $ess->getPlayerManager()->updatePlayerVisibility($player);
                        Translator::sendMessage($player, "formhandler.settings.hideplayers", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, $value ? "formhandler.settings.hiding" : "formhandler.settings.showing")]);
                    }));

                    if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                        $form->addElement(new Toggle(Translator::getTranslationPlayer($player, "forms.settings.fly"), $player->getAllowFlight(), static function (Player $player, bool $value) use ($ess) {
                            $lobbyPlugin = $ess->getServerManager()->getLobbyPlugin();
                            if ($lobbyPlugin !== null && $lobbyPlugin->getFeaturesManager()->getParkour()->isPlaying($player)) {
                                $player->sendMessage(TextFormat::RED . "You can't fly while playing parkour.");
                                return;
                            }

                            if ($ess->getPlayerData()->getString($player, PlayerData::SELECTED_RANK) === RankManager::NO_RANK || $ess->getPlayerData()->getBool($player, PlayerData::NICK)) {
                                Translator::sendMessage($player, "formhandler.settings.fly.hidingrank", Translator::TYPE_ERROR);
                            } else {
                                $player->setFlying($value);
                                $player->setAllowFlight($value);

                                Translator::sendMessage($player, "formhandler.settings.fly", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, $value ? "formhandler.settings.enabled" : "formhandler.settings.disabled")]);
                            }
                        }));
                    }
                    if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                        $form->addElement(new Toggle(Translator::getTranslationPlayer($player, 'forms.settings.announcements'), $playerData->getBool($player, PlayerData::ANNOUNCEMENTS), static function (Player $player, bool $value) use ($playerData) {
                            $playerData->setValue($player, PlayerData::ANNOUNCEMENTS, $value);
                            Translator::sendMessage(
                                $player,
                                "formhandler.settings.announcements",
                                Translator::TYPE_SUCCESS,
                                ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, $value ? "formhandler.settings.enabled" : "formhandler.settings.disabled")]
                            );
                        }));
                    }
                    if ($player->hasPermission(Permissions::RANK_ADVISOR)) {
                        $form->addElement(new Toggle(Translator::getTranslationPlayer($player, "forms.settings.knockback"), $playerData->getBool($player, PlayerData::KNOCKBACK), static function (Player $player, bool $value) use ($playerData) {
                            $playerData->setValue($player, PlayerData::KNOCKBACK, $value);
                            Translator::sendMessage($player, "formhandler.settings.knockback", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, $value ? "formhandler.settings.enabled" : "formhandler.settings.disabled")]);
                        }));
                    }
                    $form->addElement(new Toggle('FPS mode', $playerData->getBool($player, PlayerData::FPS_MODE), static function (Player $player, bool $value) use ($ess) {
                        $ess->getPlayerData()->setValue($player, PlayerData::FPS_MODE, $value);

                        if ($value) {
                            $player->sendMessage('§aEnabled FPS mode. You will no longer see fancy graphics and animations.');
                        } else {
                            $player->sendMessage('§cDisabled FPS mode. You will now see fancy graphics and animations.');
                        }

                        /*if (($presents = $ess->getPlayerManager()->getWorldFeatures()->getPresents()) !== null) {
                            $presents->sendWeather($player, $value);
                            }*/
                    }));
                    $form->addElement(new Toggle('Show Bossbar', $playerData->getBool($player, PlayerData::BOSS_BAR), static function (Player $player, bool $value) use ($ess) {
                        $ess->getPlayerData()->setValue($player, PlayerData::BOSS_BAR, $value);

                        if ($value) {
                            $player->sendMessage('§aEnabled Bossbar. The bossbar will now be shown everywhere.');
                        } else {
                            $player->sendMessage('§cDisabled Bossbar. The bossbar will be hidden everywhere except the lobby.');
                        }
                    }));

                    $form->sendForm();
                }
            }));
            $form->addButton(new Button('Minigame Settings', static function (Player $player) use ($ess, $goBack) {
                $ess->getPlayerData()->getGameSettings()->sendForm($player, $goBack);
            }));
            $form->addButton(new Button('Replays', static function (Player $player) use ($goBack) {
                ReplayManager::sendReplayMenu($player, $goBack);
            }));
            $form->addButton(new ImageButton('Language', ImageButton::IMAGE_TYPE_URL, Translator::getFlag($player->getNGLanguage()), static function (NGPlayer $player) use ($ess, $goBack) {
                Translator::sendLanguageSelector($player, $ess, $goBack);
            }));
            $form->addButton(new Button('Punishments', static function (Player $player) use ($ess, $goBack) {
                $ess->getPlayerManager()->getEnforcementHandler()->sendPunishmentsViewer($player, null, $goBack);
            }));

            $form->sendForm();
        }
    }

    public static function sendStats(Player $player, ?string $playerName = null, ?callable $callback = null, ?callable $onBack = null): void
    {
        $player->sendMessage(TextFormat::RED . "Player statistics are currently unavailable.");
        if ($onBack !== null) {
            $onBack($player);
        }
    }

    public static function sendEmojiHelpForm(Player $player): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle("Emoji Dictionary");

            $form->setContent("Backslash ('\') can be used as escape character to prevent text from being replaced.\nExample: '\:skull:'\n\nEmojis and Usages:");

            foreach (Emojis::getInstance()->getEmojisForHelpMenu() as [$emoji, $usage]) {
                $form->addButton(new Button("{$emoji} - {$usage}", function (NGPlayer $player) use ($emoji) {
                    $player->chat($emoji);
                }));
            }

            $form->sendForm();
        }
    }
}