<?php
/**
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

namespace conquests;

use conquests\generators\GeneratorEnum;
use libminigames\settings\components\Slider;
use libminigames\settings\components\Toggle;
use libminigames\settings\GameSettings;
use libminigames\settings\SettingsDescription;
use function array_filter;

final class CQSettings extends GameSettings
{
    #[SettingsDescription("Free items", "All purchasable items are free"), Toggle]
    protected bool $freeItems = false;

    #[SettingsDescription("Endless Game", "No game end by playtime."), Toggle]
    protected bool $endless = false;

    #[SettingsDescription("No block protection", "All blocks become breakable"), Toggle]
    protected bool $noProtection = false;

    #[SettingsDescription("Maxed upgrades", "All upgrades are maxed by default"), Toggle]
    protected bool $maxedUpgrades = false;

    #[SettingsDescription("Permanent jump boost", "All players have permanent III jump boost"), Toggle]
    protected bool $jumpBoost = false;

    #[SettingsDescription("Permanent speed", "All players have permanent II speed"), Toggle]
    protected bool $speed = false;

    #[SettingsDescription("Instant respawn", "All players instantly respawn"), Toggle]
    protected bool $instantRespawn = false;

    #[SettingsDescription("Item Cooldowns", "Items have use cooldowns"), Toggle]
    protected bool $cooldowns = true;

    #[SettingsDescription("Keep inventory", "All players keep their inventory on death"), Toggle]
    protected bool $keepInventory = false;

    #[SettingsDescription("Iron Generation", "Iron will spawn in generators"), Toggle]
    protected bool $generateIron = true;
    #[SettingsDescription("Gold Generation", "Gold will spawn in generators"), Toggle]
    protected bool $generateGold = true;
    #[SettingsDescription("Emerald Generation", "Emeralds will spawn in generators"), Toggle]
    protected bool $generateEmerald = true;
    #[SettingsDescription("Diamond Generation", "Diamonds will spawn in generators"), Toggle]
    protected bool $generateDiamond = true;
    #[SettingsDescription("Quick-Deposit Chests", "Click chests to deposit resources"), Toggle]
    protected bool $quickDepositChests = true;

    #[SettingsDescription("Score Amount", "Score amount required to win"), Slider(1, 10)]
    protected int $scoreAmount = 2;

    public function hasFreeItems(): bool
    {
        return $this->freeItems;
    }

    public function isEndless(): bool
    {
        return $this->endless;
    }

    public function hasNoProtection(): bool
    {
        return $this->noProtection;
    }

    public function hasMaxedUpgrades(): bool
    {
        return $this->maxedUpgrades;
    }

    public function hasJumpBoost(): bool
    {
        return $this->jumpBoost;
    }

    public function hasSpeed(): bool
    {
        return $this->speed;
    }

    public function hasInstantRespawn(): bool
    {
        return $this->instantRespawn;
    }

    public function hasCooldowns(): bool
    {
        return $this->cooldowns;
    }

    public function hasKeepInventory(): bool
    {
        return $this->keepInventory;
    }

    /**
     * @return GeneratorEnum[]
     */
    public function getEnabledGenerators(): array
    {
        return array_filter([
            $this->generateIron ? GeneratorEnum::IRON : null,
            $this->generateGold ? GeneratorEnum::GOLD : null,
            $this->generateEmerald ? GeneratorEnum::EMERALD : null,
            $this->generateDiamond ? GeneratorEnum::DIAMOND : null
        ]);
    }

    /**
     * Enables and disables generators.
     *
     * Null keeps the value the same. For example, `setEnabledGenerators(gold: true, diamond: false)` will do the following:
     * - Keep the value of iron and emerald the same.
     * - Set the value of gold to true and diamond to false
     * @return void
     */
    public function setEnabledGenerators(?bool $iron = null, ?bool $gold = null, ?bool $emerald = null, ?bool $diamond = null): void
    {
        $this->generateIron = $iron ?? $this->generateIron;
        $this->generateGold = $gold ?? $this->generateGold;
        $this->generateEmerald = $emerald ?? $this->generateEmerald;
        $this->generateDiamond = $diamond ?? $this->generateDiamond;
    }

    public function hasQuickDepositChests(): bool
    {
        return $this->quickDepositChests;
    }

    public function getScoreAmount(): int
    {
        return $this->scoreAmount;
    }
}