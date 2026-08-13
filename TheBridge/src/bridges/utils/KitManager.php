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
 * @author b1u3s
 *
 */
declare(strict_types=1);

namespace bridges\utils;

use bridges\BridgeSettings;
use bridges\menu\LayoutEditor;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Random\RandomException;

final class KitManager
{
    /**
     * Get the kit items for a specific kit type
     *
     * @param string $kitType The kit type from BridgeSettings
     * @param DyeColor $teamColor The team's dye color for terracotta
     * @param bool $noBowCooldown Whether bow cooldown is disabled
     * @param bool $instantBreakPickaxe Whether pickaxe breaks instantly
     * @param int $count The item count (1 for editor mode, normal count for gameplay)
     * @return Item[] Array of items indexed by slot preference
     * @throws RandomException
     */
    public static function getKitItems(
        string $kitType,
        DyeColor $teamColor,
        bool $noBowCooldown,
        bool $instantBreakPickaxe,
        int $count = 64
    ): array {
        return match ($kitType) {
            BridgeSettings::KIT_NO_KIT => [],
            BridgeSettings::KIT_OVERPOWERED => self::getOverpoweredKit($teamColor, $noBowCooldown, $count),
            BridgeSettings::KIT_BLOCK_ONLY => self::getBlocksOnlyKit($teamColor, $count),
            BridgeSettings::KIT_KNOCKBACK_STICK => self::getKnockbackStickKit($teamColor, $count),
            default => self::getNormalKit($teamColor, $noBowCooldown, $instantBreakPickaxe, $count),
        };
    }

    /**
     * Apply kit-specific effects to a player
     *
     * @param Player $player
     * @param string $kitType
     */
    public static function applyKitEffects(Player $player, string $kitType): void
    {
        match ($kitType) {
            BridgeSettings::KIT_BLOCK_ONLY => $player->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), 999999, 3, false)),
            default => null,
        };
    }

    /**
     * Get the protection level for armor based on kit type
     *
     * @param string $kitType
     * @return int
     */
    public static function getArmorProtectionLevel(string $kitType): int
    {
        return match ($kitType) {
            BridgeSettings::KIT_OVERPOWERED => 5,
            default => 0,
        };
    }

    private static function getNormalKit(
        DyeColor $teamColor,
        bool $noBowCooldown,
        bool $instantBreakPickaxe,
        int $count
    ): array {
        $sword = VanillaItems::IRON_SWORD();
        $sword->setUnbreakable();

        $bow = VanillaItems::BOW();
        $bow->setUnbreakable();
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));

        $pickaxe = VanillaItems::DIAMOND_PICKAXE();
        $pickaxe->setUnbreakable();
        $pickaxe->addEnchantment(new EnchantmentInstance(
            VanillaEnchantments::EFFICIENCY(),
            $instantBreakPickaxe ? 10 : 3
        ));

        return self::giveKit($teamColor, $count, $noBowCooldown, $sword, $bow, $pickaxe);
    }

    private static function getOverpoweredKit(
        DyeColor $teamColor,
        bool $noBowCooldown,
        int $count
    ): array {
        $sword = VanillaItems::DIAMOND_SWORD();
        $sword->setUnbreakable();
        $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2));

        $bow = VanillaItems::BOW();
        $bow->setUnbreakable();
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 2));
        $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1));

        $pickaxe = VanillaItems::DIAMOND_PICKAXE();
        $pickaxe->setUnbreakable();
        $pickaxe->addEnchantment(new EnchantmentInstance(
            VanillaEnchantments::EFFICIENCY(),
            10
        ));

        return self::giveKit($teamColor, $count, $noBowCooldown, $sword, $bow, $pickaxe);
    }

    private static function getBlocksOnlyKit(DyeColor $teamColor, int $count): array
    {
        $terracotta = VanillaBlocks::STAINED_CLAY()->setColor($teamColor)->asItem();
        $terracotta->setCount($count === 1 ? 1 : 64);

        return [
            LayoutEditor::TERRACOTTA_INDEX_1 => $terracotta,
            LayoutEditor::TERRACOTTA_INDEX_2 => $terracotta,
        ];
    }

    /**
     * @throws RandomException
     */
    private static function getKnockbackStickKit(DyeColor $teamColor, int $count): array
    {
        $stick = random_int(1, 67) === 1 ? VanillaItems::BAMBOO() : VanillaItems::STICK();
        $stick->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1));
        $stick->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Knockback Stick');

        $terracotta = VanillaBlocks::STAINED_CLAY()->setColor($teamColor)->asItem();
        $terracotta->setCount($count === 1 ? 1 : 64);

        return [
            LayoutEditor::SWORD_INDEX => $stick,
            LayoutEditor::TERRACOTTA_INDEX_1 => $terracotta,
            LayoutEditor::TERRACOTTA_INDEX_2 => $terracotta,
        ];
    }

    /**
     * @param DyeColor $teamColor
     * @param int $count
     * @param bool $noBowCooldown
     * @param Sword $sword
     * @param Bow $bow
     * @param Pickaxe $pickaxe
     * @return array
     */
    private static function giveKit(DyeColor $teamColor, int $count, bool $noBowCooldown, Sword $sword, Bow $bow, Pickaxe $pickaxe): array
    {
        $terracotta = VanillaBlocks::STAINED_CLAY()->setColor($teamColor)->asItem();
        $terracotta->setCount($count === 1 ? 1 : 64);

        $arrow = VanillaItems::ARROW();
        $arrow->setCustomName(TextFormat::RESET . TextFormat::GREEN . 'Arrow');
        $arrow->setLore([
            TextFormat::RESET . TextFormat::GRAY . ($noBowCooldown
                ? 'Regenerates ' . TextFormat::GREEN . 'instantly'
                : 'Regenerates every ' . TextFormat::GREEN . '3.5s') . TextFormat::GRAY . '!'
        ]);

        $goldenApple = VanillaItems::GOLDEN_APPLE();
        $goldenApple->setCount($count === 1 ? 1 : 8);

        return [
            LayoutEditor::SWORD_INDEX => $sword,
            LayoutEditor::BOW_INDEX => $bow,
            LayoutEditor::PICKAXE_INDEX => $pickaxe,
            LayoutEditor::TERRACOTTA_INDEX_1 => $terracotta,
            LayoutEditor::TERRACOTTA_INDEX_2 => $terracotta,
            LayoutEditor::GOLDEN_APPLE_INDEX => $goldenApple,
            LayoutEditor::ARROW_INDEX => $arrow,
        ];
    }
}