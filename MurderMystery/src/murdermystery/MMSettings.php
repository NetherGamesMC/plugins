<?php

/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author cooldogedev
 *
 */

declare(strict_types=1);

namespace murdermystery;

use libforms\elements\Dropdown;
use libminigames\Arena;
use libminigames\settings\components\Toggle;
use libminigames\settings\GameSettings;
use libminigames\settings\SettingsDescription;
use murdermystery\gamemodes\classic\MMArenaClassic;
use murdermystery\gamemodes\infection\MMArenaInfection;
use murdermystery\utils\MMChance;
use pocketmine\player\Player;
use function array_search;
use function array_unshift;
use function shuffle;

final class MMSettings extends GameSettings
{
    #[Toggle, SettingsDescription("Always compass", "The murderer will get the compass from the start")]
    protected bool $alwaysCompass = false;

    #[Toggle, SettingsDescription("Reveal identities", "Player nametags will be revealed at the start")]
    protected bool $revealIdentities = false;

    #[Toggle, SettingsDescription("No block protection", "All blocks become breakable")]
    protected bool $noProtection = false;

    public function hasAlwaysCompass(): bool
    {
        return $this->alwaysCompass;
    }

    public function hasRevealIdentities(): bool
    {
        return $this->revealIdentities;
    }

    public function hasNoProtection(): bool
    {
        return $this->noProtection;
    }

    public function __construct()
    {
        parent::__construct();

        $dropdownElement = static function (Arena $arena, string $text, int $type, ?Player $currentPlayer, callable $onSelect): Dropdown {
            $players = $arena->getAlivePlayers();
            $playerManager = $arena->getPlugin()->getEssentials()->getPlayerManager();

            shuffle($players);

            $playerNames = $playerManager->getPlayerNames($players);
            array_unshift($playerNames, "Random");

            if ($currentPlayer === null || ($playerIndex = array_search($playerManager->getPlayerName($currentPlayer), $playerNames, true)) === false) {
                $playerIndex = 0;
            }

            return new Dropdown($text, $playerNames, $playerIndex, static function (Player $player, int $value) use ($players, $onSelect): void {
                if ($value === 0) {
                    $onSelect($player, null);
                } else {
                    $onSelect($player, $players[$value - 1]);
                }
            });
        };

        $this->addElement(function (Arena $arena) use ($dropdownElement): ?Dropdown {
            if ($arena instanceof MMArenaInfection) {
                $currentAlpha = $arena->getAlpha();

                return $dropdownElement($arena, "Alpha", MMChance::INFECTED, $currentAlpha, static function (Player $player, ?Player $value) use ($arena, $currentAlpha): void {
                    if ($currentAlpha !== null) {
                        $arena->removeInfected($currentAlpha);
                    }

                    $arena->addInfected($value);
                });
            }

            return null;
        });


        $this->addElement(function (Arena $arena) use ($dropdownElement): ?Dropdown {
            if ($arena instanceof MMArenaClassic) {
                return $dropdownElement($arena, "Murderer", MMChance::MURDERER, $arena->getMurderer(), static function (Player $player, ?Player $value) use ($arena): void {
                    $arena->setMurderer($value);
                });
            }

            return null;
        });

        $this->addElement(function (Arena $arena) use ($dropdownElement): ?Dropdown {
            if ($arena instanceof MMArenaClassic) {
                return $dropdownElement($arena, "Detective", MMChance::DETECTIVE, $arena->getDetective(), static function (Player $player, ?Player $value) use ($arena): void {
                    if ($value !== null && !$arena->isMurderer($value)) {
                        $arena->setDetective($value);
                    } else {
                        $arena->setDetective();
                    }
                });
            }

            return null;
        });
    }
}
