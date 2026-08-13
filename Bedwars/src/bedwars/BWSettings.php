<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace bedwars;

use bedwars\generators\GeneratorEnum;
use libforms\elements\Dropdown as ElementDropdown;
use libforms\elements\Input as ElementInput;
use libforms\elements\Slider as ElementSlider;
use libforms\elements\Toggle as ElementToggle;
use libminigames\Arena;
use libminigames\settings\components\Slider;
use libminigames\settings\components\Toggle;
use libminigames\settings\GameSettings;
use libminigames\settings\SettingsDescription;
use pocketmine\utils\TextFormat;
use function array_filter;

final class BWSettings extends GameSettings
{
    public function sendSettingsAnnouncement(Arena $arena): void
    {
        if (count($elements = $this->getElements($arena)) === 0) {
            return;
        }

        $arena->broadcastMessage(TextFormat::GOLD . "Custom settings have been applied to this game!", true);
        foreach ($elements as $element) {
            $message = TextFormat::GOLD . trim(preg_replace('/\[.*$/', '', $element->getText()) ?? "") . " §r§l»§r ";
            switch ($element->getText()) {
                case 'Minimum Swap Cooldown':
                case 'Maximum Swap Cooldown':
                    if ($this->hasPositionSwap() && $element instanceof ElementSlider) {
                        $message .= TextFormat::YELLOW . $element->getDefault() . " minute" . (($element->getDefault() == 1) ? '' : 's');
                    } else {
                        continue 2;
                    }
                    break;
                case 'Iron Generation':
                case 'Gold Generation':
                case 'Emerald Generation':
                case 'Diamond Generation':
                    if ($element instanceof ElementToggle && !$element->getDefault()) {
                        $message .= TextFormat::RED . "Disabled";
                    }
                    break;
                default:
                    $message .= match (true) {
                        $element instanceof ElementToggle => $element->getDefault() ? TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled",
                        $element instanceof ElementDropdown => TextFormat::AQUA . $element->getOptions()[$element->getDefault()],
                        $element instanceof ElementInput => TextFormat::AQUA . $element->getDefault(),
                        $element instanceof ElementSlider => TextFormat::AQUA . $element->getDefault(),
                        default => "Unknown"
                    };
                    break;
            }
            $arena->broadcastMessage($message, true);
        }
    }

    #[SettingsDescription("Free items", "All purchasable items are free"), Toggle]
    protected bool $freeItems = false;

    #[SettingsDescription("Endless Game", "No forced Bed Breaks or Sudden Death"), Toggle]
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
    #[SettingsDescription("Auto Bed Defense", "Teams automatically spawn with a bed defense"), Toggle]
    protected bool $bedDefense = false;
    #[SettingsDescription("Iron Generation", "Iron will spawn in generators"), Toggle]
    protected bool $generateIron = true;
    #[SettingsDescription("Gold Generation", "Gold will spawn in generators"), Toggle]
    protected bool $generateGold = true;
    #[SettingsDescription("Emerald Generation", "Emeralds will spawn in generators"), Toggle]
    protected bool $generateEmerald = true;
    #[SettingsDescription("Diamond Generation", "Diamonds will spawn in generators"), Toggle]
    protected bool $generateDiamond = true;
    #[SettingsDescription("Quick-Deposit Chests §g§l[NEW]", "Click chests to deposit resources"), Toggle]
    protected bool $quickDepositChests = true;
    #[SettingsDescription("Play Position Swap", "Randomly swap positions with enemy teams."), Toggle]
    protected bool $positionSwap = false;
    #[SettingsDescription("Minimum Swap Cooldown", "Minimum time before a swap."), Slider(1, 15)]
    protected int $minSwapTime = 3;
    #[SettingsDescription("Maximum Swap Cooldown", "Maximum time before a swap."), Slider(1, 15)]
    protected int $maxSwapTime = 7;

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

    public function hasAutoBedDefense(): bool
    {
        return $this->bedDefense;
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

    public function hasPositionSwap(): bool
    {
        return $this->positionSwap;
    }

    /**
     * Returns delays of Position Swap in seconds.
     *
     * @return list{int, int} An associative array where keys:
     * - 'min': The minimum delay in seconds.
     * - 'max': The maximum delay in seconds. If max is less than min, max is set to min.
     */
    public function getPositionSwapDelays(): array
    {
        if ($this->maxSwapTime < $this->minSwapTime) {
            $this->maxSwapTime = $this->minSwapTime;
        }

        return [60 * $this->minSwapTime, 60 * $this->maxSwapTime];
    }
}