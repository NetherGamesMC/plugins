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

namespace NetherGames\NGEssentials\player\enforcement;

use DateInterval;
use DateTime;
use libforms\elements\Button;
use libforms\elements\Input;
use libforms\FormManager;
use libforms\SimpleForm;
use libminigames\utils\Utils;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use RuntimeException;
use function array_filter;
use function array_key_last;
use function count;
use function explode;
use function glob;
use function implode;
use function in_array;
use function is_numeric;
use function json_decode;
use function str_replace;
use const GLOB_ONLYDIR;
use const JSON_THROW_ON_ERROR;

class ReplayManager
{
    public function __construct(Enforcement $enforcer)
    {
        $plugin = $enforcer->getPlugin();

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function () use ($plugin): void {
            $plugin->getLogger()->debug('Removing expired replays...');
            \libReplay\session\replay\ReplayManager::removeUnusedReplays();
        }), 5 * 60 * 20);

        $replays = glob($plugin->getServer()->getDataPath() . '/worlds/Replay-*', GLOB_ONLYDIR);

        if ($replays !== false) {
            foreach ($replays as $replay) {
                try {
                    Filesystem::recursiveUnlink($replay);
                } catch (RuntimeException $exception) {

                }
            }
        }
    }

    public static function sendReplayMenu(Player $player, ?callable $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $goBack = static function (Player $player) use ($onBack) {
                self::sendReplayMenu($player, $onBack);
            };

            $form->setTitle('Replay');
            $form->setBackClosure($onBack);

            $form->addButton(new Button('Recent Matches', static function (Player $player) use ($goBack) {
                self::sendRecentMatches($player, $goBack);
            }));
            $form->addButton(new Button('Search by ReplayID', static function (Player $player) use ($goBack) {
                self::sendMatchSearcher($player, $goBack);
            }));

            $form->sendForm();
        }
    }

    private static function sendMatchSelector(Player $player, array $rows, callable $onBack, callable $goBack, string $playerName = ''): void
    {
        $expireDate = new DateTime();
        $expireDate->sub(new DateInterval('P2D'));

        FormManager::createSimplePaginatedForm($player, $rows, static function (SimpleForm $form, int $page, array $rows) use ($onBack, $goBack, $playerName, $expireDate) {
            $form->setTitle("Recent Matches: " . ($playerName !== "" ? TextFormat::RESET . $playerName : "") . " - Page " . ($page + 1));
            $form->setBackClosure($onBack);

            foreach ($rows as $row) {
                $dateTime = new DateTime();
                $dateTime->setTimestamp($row['time']);

                if ($dateTime->getTimestamp() < $expireDate->getTimestamp()) {
                    continue;
                }

                $e = explode('-', $row['map_name']);
                $mapDisplayName = str_replace('_', ' ', $e[array_key_last($e)]);

                $form->addButton(new Button(TextFormat::YELLOW . ServerManager::getName($row['server_type']) . ': ' . $mapDisplayName . TextFormat::EOL . TextFormat::GRAY . $dateTime->format('Y-m-d H:i:s'), static function (Player $player) use ($row, $goBack, $playerName) {
                    self::sendReplaySummary($player, $row, static function (Player $player) use ($goBack, $playerName) {
                        self::sendRecentMatches($player, $goBack, $playerName);
                    });
                }));
            }
        }, 25, $onBack);
    }

    public static function sendRecentMatches(Player $player, callable $onBack, string $playerName = ''): void
    {
        $callable = static function (array $rows) use ($player, $onBack, $playerName) {
            if ($player->isConnected()) {
                $goBack = static function (Player $player) use ($onBack, $playerName) {
                    self::sendRecentMatches($player, $onBack, $playerName);
                };

                self::sendMatchSelector($player, $rows, $onBack, $goBack, $playerName);
            }
        };

        MySQLCredentials::executeSelect('replay.load_by_player', ['players' => '%"' . ($playerName === '' ? $player->getName() : $playerName) . '"%'], $callable, function (SqlError $error) use ($callable): void {
            $callable([]);
        });
    }

    public static function sendRecentMatchesByIds(Player $player, array $replayIds, callable $onBack, string $playerName = ''): void
    {
        $callable = static function (array $rows) use ($player, $onBack, $replayIds, $playerName) {
            if ($player->isConnected()) {
                $goBack = static function (Player $player) use ($replayIds, $onBack, $playerName) {
                    self::sendRecentMatchesByIds($player, $replayIds, $onBack, $playerName);
                };

                self::sendMatchSelector($player, array_filter($rows, fn($row) => in_array($row['replay_id'], $replayIds)), $onBack, $goBack, $playerName);
            }
        };

        MySQLCredentials::executeSelect('replay.load_by_player', ['players' => '%"' . ($playerName === '' ? $player->getName() : $playerName) . '"%'], $callable, function (SqlError $error) use ($callable): void {
            $callable([]);
        });
    }

    public static function sendReplaySummary(Player $player, array $row, callable $goBack): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($row['time']);

            $e = explode('-', $row['map_name']);
            $mapDisplayName = str_replace('_', ' ', $e[array_key_last($e)]);

            $replayId = $row['replay_id'];

            $form->setTitle('Replay #' . $replayId);

            $content = [];
            if ($row['private'] === 1) {
                $content[] = TextFormat::GOLD . 'Private Game' . TextFormat::GRAY . ': ' . TextFormat::GREEN . ServerManager::getName($row['server_type']);
            } elseif ($row['touch_only'] === 1) {
                $content[] = TextFormat::GOLD . 'Touch Only Game' . TextFormat::GRAY . ': ' . TextFormat::GREEN . ServerManager::getName($row['server_type']);
            } else {
                $content[] = 'Game: ' . TextFormat::GREEN . ServerManager::getName($row['server_type']);
            }

            if ($row['game_type'] !== '') {
                $content[] = 'Mode: ' . TextFormat::GREEN . $row['game_type'];
            }
            $content[] = 'Map: ' . TextFormat::GREEN . $mapDisplayName;
            $content[] = ' ';
            $content[] = 'Players: ' . TextFormat::GREEN . Utils::getPrettyList(json_decode($row['players'], true, 512, JSON_THROW_ON_ERROR));
            $content[] = ' ';
            $content[] = 'Date: ' . TextFormat::GREEN . $dateTime->format('d/m/Y');
            $content[] = 'Time: ' . TextFormat::GREEN . $dateTime->format('H:i') . ' (UTC)';

            $form->setContent(implode(TextFormat::EOL . TextFormat::RESET, $content));

            if ($player->hasPermission(Permissions::RANK_LEGEND) || $player->hasPermission(Permissions::RANK_TRAINEE) || $player->hasPermission(Permissions::TIER_AMETHYST)) {
                $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GREEN . 'Watch', static function (Player $player) use ($replayId) {
                    $ess = NGEssentials::getInstance();
                    $socialManager = $ess->getPlayerManager()->getSocialManager();

                    if ($socialManager->getPartyManager()->getParty($player) === null) {
                        $playerData = $ess->getPlayerData();

                        $player->getInventory()->setHeldItemIndex(3);

                        if ($ess->getServerManager()->getServerType() === ServerManager::REPLAY) {
                            if (MySQLCredentials::isDatabaseOnline() && ($replayManager = \libReplay\session\replay\ReplayManager::getInstance()) !== null) {
                                $replayManager->stopReplay($player, $player->getWorld());
                                $replayManager->loadReplay($player, $replayId);
                            } else {
                                $player->sendMessage(TextFormat::RED . "Replays can't be loaded at the moment. Please try again later.");
                            }
                        } else {
                            $playerData->setValue($player, PlayerData::REPLAY, $replayId);
                            if (($currentTracking = $playerData->getString($player, PlayerData::TRACK)) !== '') {
                                $playerData->setValue($player, PlayerData::TRACK, '');
                            }

                            $onMatchmakingFailure = static function () use ($playerData, $player, $currentTracking): void {
                                $playerData->unsetValue($player, PlayerData::REPLAY);
                                if ($currentTracking !== '') {
                                    $playerData->setValue($player, PlayerData::TRACK, $currentTracking);
                                }
                            };
                            if (!$ess->getPlayerManager()->transferPlayer($player, ServerManager::REPLAY, '', false, $onMatchmakingFailure)) {
                                $onMatchmakingFailure();
                            }
                        }


                    } else {
                        $player->sendMessage(TextFormat::RED . 'You cannot watch a replay while in a party. Leave the party first, then try again.');
                    }
                }));
            } else {
                $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GRAY . 'No Permission', static function (Player $player) {
                    $player->sendMessage(TextFormat::RED . "You don't have permission to watch replays! Buy the §l§bLEGEND§r §crank at §bngmc.co/store §cto use this feature!");
                }));
            }
            $form->setButton2(new Button(TextFormat::BOLD . TextFormat::RED . 'Back', $goBack));

            $form->sendForm();
        }
    }

    public static function sendMatchSearcher(Player $player, callable $goBack): void
    {
        $form = FormManager::createCustomForm($player, $goBack);

        if ($form !== null) {
            $form->setTitle('Match Selector');

            $form->addElement(new Input('Replay ID', 'id', '', static function (Player $player, string $value) use ($goBack) {
                if (is_numeric($value)) {
                    MySQLCredentials::executeSelect('replay.load_by_replay_id', ['replay_id' => (int)$value], static function (array $rows) use ($player, $goBack) {
                        if ($player->isConnected()) {
                            if (count($rows) === 0) {
                                $player->sendMessage(TextFormat::RED . "That replay doesn't exist.");
                            } else {
                                self::sendReplaySummary($player, $rows[0], static function (Player $player) use ($goBack) {
                                    self::sendRecentMatches($player, $goBack);
                                });
                            }
                        }
                    }, static function (SqlError $error) use ($player): void {
                        if ($player->isConnected()) {
                            $player->sendMessage(TextFormat::RED . "Replays can't be loaded at the moment. Please try again later.");
                        }
                    });
                } else {
                    $player->sendMessage(TextFormat::RED . "The replay ID must be a numeric value.");
                }
            }));

            $form->sendForm();
        }
    }
}