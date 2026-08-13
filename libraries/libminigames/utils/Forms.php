<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\utils;

use libforms\elements\Button;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\FormManager;
use libforms\SimpleForm;
use libminigames\Arena;
use libminigames\events\MinigameQuitEvent;
use libminigames\Minigame;
use libminigames\Team;
use libminigames\TeamArena;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_key_first;
use function array_keys;
use function array_search;
use function count;

abstract class Forms
{
    public static function sendReplayMenu(Minigame $plugin, Player $player, bool $replay = true): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $ess = $plugin->getEssentials();
            $playerManager = $ess->getPlayerManager();
            $partyManager = $playerManager->getSocialManager()->getPartyManager();

            $callable = static function (Player $player, string $mode) use ($plugin) {
                /** @var NGPlayer $player */
                $serverManager = $plugin->getEssentials()->getServerManager();

                if (($arena = $plugin->getArena($player)) !== null) {
                    $arena->removePlayer($player, MinigameQuitEvent::END, true, false);
                }

                if ($plugin->isStandAloneGame()) {
                    $playerManager = $plugin->getEssentials()->getPlayerManager();

                    if (($gameType = $serverManager->getGameType()) === '' || $gameType === $mode) {
                        $onMatchmakingFailure = static function () use ($plugin, $player): void {
                            if ($player->isConnected()) {
                                $plugin->joinArena($player);
                            }
                        };

                        if ($plugin->canJoinArena($player, $plugin->getModeId($mode)) || !$playerManager->transferPlayer($player, $serverManager->getServerType(), $serverManager->getGameType(), true, $onMatchmakingFailure)) {
                            $onMatchmakingFailure();
                        }
                    } else {
                        $onMatchmakingFailure = static function () use ($playerManager, $player): void {
                            if ($player->isConnected()) {
                                $playerManager->transferPlayer($player);
                            }
                        };

                        if (!$playerManager->transferPlayer($player, $serverManager->getServerType(), $mode, true, $onMatchmakingFailure)) {
                            $onMatchmakingFailure();
                        }
                    }
                } else {
                    $plugin->joinArena($player, $plugin->getModeId($mode));
                }
            };

            $modeSelector = static function (Player $player, string $mode) use ($plugin, $playerManager, $partyManager, $callable) {
                if (($party = $partyManager->getParty($player)) === null) {
                    $callable($player, $mode);
                } elseif (($arena = $plugin->getArena($player)) === null) {
                    $callable($player, $mode);
                } else {
                    $ingamePlayers = $arena->isRunning() ? array_filter($partyManager->getPlayers($party), fn(Player $member) => !$arena->isSpectator($member) && $member !== $player) : [];

                    if (count($ingamePlayers) === 0) {
                        $callable($player, $mode);
                    } else {
                        $form = FormManager::createModalForm($player);

                        if ($form !== null) {
                            $form->setTitle('Quit the match');

                            if (count($ingamePlayers) === 1) {
                                $form->setContent($playerManager->getPlayerName($ingamePlayers[array_key_first($ingamePlayers)]) . ' is still in the game. Are you sure you wish to take them to the lobby?');
                            } else {
                                $form->setContent(Utils::getPrettyList($playerManager->getPlayerNames($ingamePlayers)) . ' are still in the game. Are you sure you wish to take them to the lobby?');
                            }

                            $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GREEN . 'Yes', static function (Player $player) use ($callable, $mode) {
                                $callable($player, $mode);
                            }));
                            $form->setButton2(new Button(TextFormat::BOLD . TextFormat::RED . 'No'));

                            $form->sendForm();
                        }
                    }
                }
            };

            $modes = $plugin->getModes();
            if (count($modes) > 1) {
                if ($replay) {
                    if (($arena = $plugin->getArena($player)) === null || $player->isSneaking()) {
                        $form->setTitle('Play again?');
                    } else {
                        $modeSelector($player, $modes[$arena->getModeId()]);
                        return;
                    }
                } else {
                    $form->setTitle($plugin->getMinigameName());
                }
            } else {
                $modeSelector($player, $modes[array_key_first($modes)]);
                return;
            }

            $serverType = $plugin->getEssentials()->getServerManager()->getServerType();
            $icon = ServerManager::getIcon($serverType);
            if (($gameTypesCount = count($modes)) < 5) {
                $form->setType(SimpleForm::getDynamicType($gameTypesCount));
            }

            foreach ($modes as $gameType) {
                $cluster = $ess->getServerManager()->getCluster($serverType, $gameType);

                $form->addButton(new ImageButton(TextFormat::GOLD . $gameType . TextFormat::EOL . $cluster->getStringStatus($player), ImageButton::IMAGE_TYPE_PATH, $icon, static function (Player $player) use ($modeSelector, $gameType) {
                    $modeSelector($player, $gameType);
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendTeleporter(Player $player, Arena $arena): void
    {
        /** @var \NetherGames\NGEssentials\player\NGPlayer $player */

        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $playerManager = $arena->getPlugin()->getEssentials()->getPlayerManager();
            $playerNames = [];

            if ($arena instanceof TeamArena) {
                foreach ($arena->getAliveTeams() as $aliveTeam) {
                    foreach ($aliveTeam->getAlivePlayers() as $alivePlayer) {
                        $playerNames[] = $aliveTeam->getPlayerName($alivePlayer);
                    }
                }
            } else {
                $playerNames = $playerManager->getPlayerNames($arena->getAlivePlayers());
            }

            if (empty($playerNames)) {
                $player->sendConditionalMessage(TextFormat::RED . "No available players to teleport to!");
                return;
            }

            $form->setTitle('Spectator Menu');
            $form->setContent('Select a player:');

            foreach ($playerNames as $playerName) {
                $clearPlayerName = TextFormat::clean($playerName);
                $form->addButton(new ImageButton('Teleport to ' . $playerName, ImageButton::IMAGE_TYPE_FACE, $clearPlayerName, static function (Player $player) use ($playerManager, $arena, $clearPlayerName) {
                    if ($arena->isInArena($player)) {
                        if ($arena->isRunning()) {
                            if (($ingamePlayer = $playerManager->getBestMatchingPlayer($clearPlayerName)) instanceof Player && $arena->isInArena($ingamePlayer) && !$arena->isSpectator($ingamePlayer)) {
                                $player->teleport($ingamePlayer->getLocation());
                            } else {
                                $player->sendMessage(TextFormat::RED . 'That player is not in this game anymore.');
                            }
                        } else {
                            $player->sendMessage(TextFormat::RED . 'That game is not running.');
                        }
                    } else {
                        $player->sendMessage(TextFormat::RED . "You're not in that game anymore!");
                    }
                }));
            }

            $form->addButton(new ImageButton(TextFormat::RED . TextFormat::BOLD . 'Exit', ImageButton::IMAGE_TYPE_PATH, 'textures/blocks/barrier'));

            $form->sendForm();
        }
    }

    public static function sendTeamSelector(Player $player, TeamArena $arena): void
    {
        if ($arena->isCreator($player)) {
            self::sendTeamDistributor($player, $arena);
            return;
        }

        if (!($isPrivate = $arena->isPrivateGame()) && !$player->hasPermission(Permissions::RANK_EMERALD)) {
            $player->sendMessage(TextFormat::RED . "You don't have permission to choose a team. Buy the §l§aEMERALD §r§cor §l§bLEGEND §r§crank at §bngmc.co/store §cto choose one!");
            return;
        }

        if ($isPrivate) {
            if (!$arena->getGameSettings()->isTeamChangingAllowed()) {
                $player->sendMessage(TextFormat::YELLOW . "Party host has disabled team changing.");
                return;
            }
        } else if ($arena->isSoloGame()) {
            $player->sendMessage(TextFormat::YELLOW . "You are in " . $arena->getTeam($player)->getDisplayName() . TextFormat::YELLOW . " team!");
            return;
        } else if (
            !$arena->isPartyGame() &&
            ($party = $arena->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager()->getParty($player)) !== null &&
            $party->getLeader() !== $player
        ) {
            $player->sendMessage(TextFormat::RED . "Only the party leader can change teams.");
            return;
        }

        $form = FormManager::createSimpleForm($player);
        if ($form !== null) {
            $form->setTitle('Team Selector');
            $form->setContent('Select a team:');

            foreach ($arena->getTeams() as $selectedTeam) {
                $form->addButton(new ImageButton(
                    $selectedTeam->getDisplayName() . " Team §7(§e" . $selectedTeam->getSize() . "§7/§e" . $arena->getTeamSize() . "§7)",
                    ImageButton::IMAGE_TYPE_PATH,
                    'textures/blocks/' . Utils::convertDamageToWoolName($selectedTeam->getDyeColor()),
                    static function (Player $player) use ($arena, $selectedTeam): void {
                        if ($arena->isInArena($player)) {
                            if ($arena->isWaiting()) {
                                $partyManager = $arena->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();
                                $party = $partyManager->getParty($player);
                                $shouldMoveParty = $party !== null && !$arena->isPartyGame();

                                if (($error = $selectedTeam->canJoinTeam($player, $shouldMoveParty ? $party->getTotalMembers() : 1)) !== null) {
                                    $player->sendMessage(TextFormat::RED . $error);
                                } else if ($shouldMoveParty) {
                                    /** @var Party $party */
                                    /** @phpstan-ignore-next-line */
                                    foreach ($partyManager->getPlayers($party) as $member) {
                                        if (!$arena->isInArena($member)) {
                                            $player->sendMessage("§c" . $member->getDisplayName() . " §cis not in the arena anymore, skipping them.");
                                            continue;
                                        }
                                        $arena->getTeamNull($member)?->removePlayer($member, true);
                                        $selectedTeam->addPlayer($member, true);
                                        $selectedTeam->queuePlayer($member);
                                        $member->sendMessage('§eYour party joined the ' . $selectedTeam->getDisplayName() . ' §eteam');
                                    }
                                } else {
                                    $arena->getTeamNull($player)?->removePlayer($player, true);
                                    $selectedTeam->addPlayer($player, true);
                                    $selectedTeam->queuePlayer($player);
                                    $player->sendMessage('§eYou joined the ' . $selectedTeam->getDisplayName() . ' §eteam');
                                }
                            } else {
                                $player->sendMessage(TextFormat::RED . 'You cannot change teams now - the game is starting!');
                            }
                        }
                    }
                ));
            }

            $form->addButton(new ImageButton(TextFormat::RED . TextFormat::BOLD . 'Exit', ImageButton::IMAGE_TYPE_PATH, 'textures/blocks/barrier'));
            $form->sendForm();
        }
    }

    public static function sendTeamDistributor(Player $player, TeamArena $arena): void
    {
        $form = FormManager::createCustomForm($player);

        if ($form !== null) {
            $wasPaused = $arena->getGameSettings()->isPaused();

            $arena->getGameSettings()->setPaused(true);

            $form->setCloseClosure(function (Player $player) use ($arena, $wasPaused): void {
                $arena->getGameSettings()->setPaused($wasPaused);
            });
            $form->setCallable(function (Player $player) use ($form): void {
                if (($closure = $form->getCloseClosure()) !== null) {
                    $closure($player);
                }
            });

            $form->setTitle("Team Distributor");

            $teamOptions = array_reduce($arena->getTeams(), fn(array $carry, Team $team): array => $carry + [
                    $team->getDisplayName() => $team
                ], []);


            $players = $arena->getAlivePlayers();
            usort($players, fn(Player $a, Player $b) => strcmp(TextFormat::clean($a->getDisplayName()), TextFormat::clean($b->getDisplayName())));

            foreach ($players as $gamePlayer) {
                $originalTeam = $arena->getTeam($gamePlayer);
                $originalTeamFormatted = $originalTeam->getDisplayName();
                $text = $originalTeam->getPlayerName($gamePlayer);
                $options = array_keys($teamOptions);
                $defaultOption = array_search($originalTeamFormatted, $options, true);

                if ($defaultOption === false) {
                    throw new \RuntimeException("Team $originalTeamFormatted not found for player $gamePlayer");
                }

                $form->addElement(new Dropdown(
                    TextUtils::center($text),
                    $options,
                    $defaultOption,
                    function (Player $player, int $value) use ($gamePlayer, $teamOptions, $options, $originalTeam): void {
                        if (!$gamePlayer->isConnected()) {
                            $player->sendMessage(TextFormat::RED . "That player is not connected anymore.");
                            return;
                        }

                        /** @var string $selectedOption */
                        $selectedOption = $options[$value];
                        /** @var Team $selectedTeam */
                        $selectedTeam = $teamOptions[$selectedOption];

                        $originalTeam->removePlayer($gamePlayer, true);
                        $selectedTeam->addPlayer($gamePlayer, true);
                        $selectedTeam->queuePlayer($gamePlayer);
                        $gamePlayer->sendMessage(TextFormat::YELLOW . "The party host has set you to join " . $selectedTeam->getDisplayName() . " team");
                    }
                ));
            }

            $form->sendForm();
        }
    }

    public static function sendMapSelector(Player $player, Arena $arena): void
    {
        $maps = $arena->getMaps();
        $plugin = $arena->getPlugin();

        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setType(SimpleForm::FORM_VOTE);
            $form->setTitle(TextFormat::YELLOW . "Map Voting");

            foreach ($maps as $button => $map) {
                $mapDisplayName = $plugin->getMapDisplayName($map, true);

                $form->addButton(new ImageButton($mapDisplayName, ImageButton::IMAGE_TYPE_MAP, $map, static function (Player $player) use ($arena, $plugin, $button, $map) {
                    /** @var NGPlayer $player */
                    if ($arena->isWaiting()) {
                        $arena->addMapVote($player, $button);
                        $player->sendConditionalMessage(TextFormat::GREEN . 'You voted for ' . TextFormat::GOLD . $plugin->getMapDisplayName($map));
                    } else {
                        $player->sendConditionalMessage(TextFormat::RED . 'You cannot vote for a map now - the game is starting!');
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendTypeSelector(Player $player, TypeArena $arena): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Mode Selector');
            $form->setContent('Choose the mode you want to play:');

            $typeVotes = $arena->getTypeVotes();

            foreach ($arena::getTypes() as $type => $typeName) {
                $votes = $typeVotes[$type] ?? 0;
                $form->addButton(new Button($typeName . ' mode [' . $votes . ']', static function (Player $player) use ($arena, $type, $typeName) {
                    /** @var TypeArena&Arena $arena */
                    if ($arena->isWaiting()) {
                        $arena->addTypeVote($player, $type);
                        $player->sendMessage(TextFormat::GREEN . 'You voted for ' . TextFormat::GOLD . $typeName);
                    } else {
                        $player->sendMessage(TextFormat::RED . 'You cannot vote for a mode now - the game is starting!');
                    }
                }));
            }

            $form->sendForm();
        }
    }

    public static function sendSettingsMenu(Player $player, Arena $arena): void
    {
        $gameConfigurationForm = $arena->getGameSettings()->asForm(
            arena: $arena,
            title: "Game Configuration",
            toSend: $player
        );
        $gameConfigurationForm->sendForm();
    }
}