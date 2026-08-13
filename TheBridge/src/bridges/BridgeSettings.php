<?php

/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
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

namespace bridges;

use libminigames\settings\components\Dropdown;
use libminigames\settings\components\Slider;
use libminigames\settings\components\Toggle;
use libminigames\settings\GameSettings;
use libminigames\settings\SettingsDescription;

final class BridgeSettings extends GameSettings
{
    public const KIT_NO_KIT = "No Kit";
    public const KIT_NORMAL = "Normal";
    public const KIT_OVERPOWERED = "Overpowered";
    public const KIT_BLOCK_ONLY = "Blocks Only";
    public const KIT_KNOCKBACK_STICK = "Knockback Stick";

    #[Toggle, SettingsDescription("Instant kill sword", "The sword will kill instantly")]
    protected bool $instantSword = false;

    #[Toggle, SettingsDescription("No bridge", "The bridge that connects the islands will be removed")]
    protected bool $noBridge = false;

    #[Toggle, SettingsDescription("No block protection", "All blocks will become breakable")]
    protected bool $noProtection = false;

    #[Toggle, SettingsDescription("Endless Game", "No game end by playtime.")]
    protected bool $endlessGame = false;

    #[Toggle, SettingsDescription("No WorldGuard", "Disables the worldguard.")]
    protected bool $noWorldGuard = false;

    #[Dropdown([self::KIT_NO_KIT, self::KIT_NORMAL, self::KIT_OVERPOWERED, self::KIT_BLOCK_ONLY, self::KIT_KNOCKBACK_STICK]), SettingsDescription("Kit", "Spawns the player with no equipment.")]
    protected string $kit = self::KIT_NORMAL;

    #[Toggle, SettingsDescription("No Bow Cooldown", "Removed the bow cooldown.")]
    protected bool $noBowCooldown = false;

    #[Toggle, SettingsDescription("Instant Break Pickaxe", "The pickaxe will break blocks instantly")]
    protected bool $instantBreakPickaxe = false;

    #[Slider(0, 20), SettingsDescription("Goal Limit", "Sets the goal limit for the game.")]
    protected int $goalLimit = 5;

    #[Toggle, SettingsDescription("Sumo Mode", "Builds a platform on bridge level over the void.")]
    protected bool $sumoMode = false;

    public function hasInstantSword(): bool
    {
        return $this->instantSword;
    }

    public function hasNoBridge(): bool
    {
        return $this->noBridge;
    }

    public function hasNoProtection(): bool
    {
        return $this->noProtection;
    }

    public function hasEndlessGame(): bool
    {
        return $this->endlessGame;
    }

    public function hasNoWorldGuard(): bool
    {
        return $this->noWorldGuard;
    }

    public function getKit(): string
    {
        return $this->kit;
    }

    public function hasNoBowCooldown(): bool
    {
        return $this->noBowCooldown;
    }

    public function hasInstantBreakPickaxe(): bool
    {
        return $this->instantBreakPickaxe;
    }

    public function getGoalLimit(): int
    {
        return $this->goalLimit;
    }

    public function hasSumoMode(): bool
    {
        return $this->sumoMode;
    }
}
