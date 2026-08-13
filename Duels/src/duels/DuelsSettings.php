<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace duels;

use libminigames\settings\components\Toggle;
use libminigames\settings\GameSettings;
use libminigames\settings\SettingsDescription;

final class DuelsSettings extends GameSettings
{
    #[Toggle, SettingsDescription("No Build Height Limit", "Removes the build height limit.")]
    protected bool $buildHeightLimit = false;

    #[Toggle, SettingsDescription("Heal on kill", "Fully regenerates the player when getting a kill.")]
    protected bool $healOnKill = false;

    #[Toggle, SettingsDescription("Re-Kit on kill", "Fully reequips the player when getting a kill.")]
    protected bool $rekitOnKill = false;

    #[Toggle, SettingsDescription("Respawns", "Respawns the player on death.")]
    protected bool $respawns = false;

    #[Toggle, SettingsDescription("Re-Kit on respawn", "Fully reequips the player when respawning.")]
    protected bool $rekitOnRespawn = false;

    #[Toggle, SettingsDescription("Endless Game", "No game end by playtime.")]
    protected bool $endlessGame = false;

    public function hasNoBuildHeightLimit(): bool
    {
        return $this->buildHeightLimit;
    }

    public function hasHealOnKill(): bool
    {
        return $this->healOnKill;
    }

    public function hasRekitOnKill(): bool
    {
        return $this->rekitOnKill;
    }

    public function hasRespawns(): bool
    {
        return $this->respawns;
    }

    public function hasRekitOnRespawn(): bool
    {
        return $this->rekitOnRespawn;
    }

    public function hasEndlessGame(): bool
    {
        return $this->endlessGame;
    }
}