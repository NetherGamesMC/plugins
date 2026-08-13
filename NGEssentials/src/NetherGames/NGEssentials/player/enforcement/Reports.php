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

use libforms\elements\ImageButton;
use libforms\FormManager;
use libminigames\Team;
use libminigames\TeamArena;
use NetherGames\NGEssentials\player\enforcement\objects\ReportedPlayerData;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;

class Reports
{
    public function __construct(private Enforcement $enforcer)
    {
    }

    public function sendReporter(Player $player): void
    {
        /** @var NGPlayer $player */
        $arena = $this->getEnforcer()->getPlugin()->getPlayerManager()->isInArena($player, true);

        $playerList = $arena instanceof TeamArena ? $arena->getPlayers() : $player->getWorld()->getPlayers();

        if (count($playerList) < 2) {
            $player->sendConditionalMessage(TextFormat::RED . "No players to report!\n" .
                TextFormat::RESET . "Use " . TextFormat::YELLOW . "/report <IGN> " . TextFormat::RESET . "to report a player not in your game.");
            return;
        }

        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle(Translator::getTranslationPlayer($player, "forms.reporter"));
            $form->setContent('Select a player to report:');

            $playerManager = $this->getEnforcer()->getManager();

            foreach (array_diff($playerList, [$player]) as $reported) {
                if ($arena instanceof TeamArena) {
                    if (($reportedTeam = $arena->getTeamNull($reported)) instanceof Team) {
                        $reportedName = $reportedTeam->getPlayerName($reported);
                    } elseif ($arena->isSpectator($reported)) {
                        $reportedName = TextFormat::GRAY . $playerManager->getPlayerName($reported);
                    } else {
                        $reportedName = $playerManager->getPlayerName($reported);
                    }
                } else {
                    $reportedName = $playerManager->getPlayerName($reported);
                }

                $form->addButton(new ImageButton('Report ' . $reportedName, ImageButton::IMAGE_TYPE_FACE, $reported->getName(), function (Player $player) use ($reported): void {
                    $this->sendPlayerReporter($player, ReportedPlayerData::fromPlayer($reported), function (Player $player): void {
                        $this->sendReporter($player);
                    });
                }));
            }

            $form->sendForm();
        }
    }

    public function getEnforcer(): Enforcement
    {
        return $this->enforcer;
    }

    public function sendPlayerReporter(Player $player, ReportedPlayerData $reportedData, ?callable $onBack = null): void
    {
        // implementation required
    }

    public function addReport(ReportedPlayerData $reportedData, string $reason, Player $reporter): void
    {
        // implementation required
    }
}