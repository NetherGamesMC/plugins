<?php

/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars;

use libminigames\settings\GameSettings;
use skywars\kits\Kit;

final class SWArenaSettings extends GameSettings
{
    protected const SETTINGS_BORDER_SIZE_MIN = 80;
    protected const SETTINGS_BORDER_SIZE_MAX = 200;
    protected const SETTINGS_BORDER_SIZE_DEFAULT = SWArenaSettings::SETTINGS_BORDER_SIZE_MIN;

    protected const SETTINGS_BORDER_TIME_MIN = 10;
    protected const SETTINGS_BORDER_TIME_MAX = 60;
    protected const SETTINGS_BORDER_TIME_DEFAULT = SWArenaSettings::SETTINGS_BORDER_TIME_MIN;

    protected const SETTINGS_BORDER_DAMAGE_MIN = 1;
    protected const SETTINGS_BORDER_DAMAGE_MAX = 10;
    protected const SETTINGS_BORDER_DAMAGE_DEFAULT = SWArenaSettings::SETTINGS_BORDER_DAMAGE_MIN;

    #[SettingsDescription("Border", "Enables the border")]
    #[Toggle]
    protected bool $border = false;

    #[SettingsDescription("Border Size", "Border's initial size in blocks")]
    #[Slider(SWArenaSettings::SETTINGS_BORDER_SIZE_MIN, SWArenaSettings::SETTINGS_BORDER_SIZE_MAX)]
    protected int $borderSize = SWArenaSettings::SETTINGS_BORDER_SIZE_DEFAULT;

    #[SettingsDescription("Border Timer", "Border shrink time in seconds")]
    #[Slider(SWArenaSettings::SETTINGS_BORDER_TIME_MIN, SWArenaSettings::SETTINGS_BORDER_TIME_MAX)]
    protected int $borderTime = SWArenaSettings::SETTINGS_BORDER_TIME_DEFAULT;

    #[SettingsDescription("Border Damage", "Border damage intensity in hearts")]
    #[Slider(SWArenaSettings::SETTINGS_BORDER_DAMAGE_MIN, SWArenaSettings::SETTINGS_BORDER_DAMAGE_MAX)]
    protected int $borderDamage = SWArenaSettings::SETTINGS_BORDER_DAMAGE_MIN;

    #[SettingsDescription("Free Kits", "Enables free kits")]
    #[Toggle]
    protected bool $freeKits = false;

    #[SettingsDescription("Position Switch", "Switch positions with a random player every minute")]
    #[Toggle]
    protected bool $positionSwitch = false;

    #[SettingsDescription("Type", "Game type")]
    #[Dropdown(["Normal", "Insane"])]
    protected string $type = "Normal";

    /**
     * @phpstan-var array<int, Kit>
     */
    protected array $kits = [];

    public function hasBorder(): bool
    {
        return $this->border;
    }

    public function getBorderSize(): int
    {
        return $this->borderSize;
    }

    public function getBorderTime(): int
    {
        return $this->borderTime;
    }

    public function getBorderDamage(): int
    {
        return $this->borderDamage;
    }

    public function hasFreeKits(): bool
    {
        return $this->freeKits;
    }

    public function hasPositionSwitch(): bool
    {
        return $this->positionSwitch;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKit(int $player): ?Kit
    {
        return $this->kits[$player] ?? null;
    }

    public function setKit(int $player, ?Kit $kit): void
    {
        $this->kits[$player] = $kit;
    }
}
