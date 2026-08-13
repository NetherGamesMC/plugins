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
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\utils;

use pocketmine\block\Block;
use pocketmine\block\StainedGlass;
use pocketmine\block\StainedHardenedClay;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\Wool;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_search;
use function assert;
use function is_a;

class Utils extends \libminigames\utils\Utils
{
    /** @var int[][] */
    public const TEXTFORMAT_RGB = [
        [0, 0, 0],
        [0, 0, 170],
        [0, 170, 0],
        [0, 170, 170],
        [170, 0, 0],
        [170, 0, 170],
        [255, 170, 0],
        [170, 170, 170],
        [85, 85, 85],
        [85, 85, 255],
        [85, 255, 85],
        [85, 255, 255],
        [255, 85, 85],
        [255, 85, 255],
        [255, 255, 85],
        [255, 255, 255]
    ];

    /** @var string[] */
    public const TEXTFORMAT_LIST = [
        TextFormat::BLACK,
        TextFormat::DARK_BLUE,
        TextFormat::DARK_GREEN,
        TextFormat::DARK_AQUA,
        TextFormat::DARK_RED,
        TextFormat::DARK_PURPLE,
        TextFormat::GOLD,
        TextFormat::GRAY,
        TextFormat::DARK_GRAY,
        TextFormat::BLUE,
        TextFormat::GREEN,
        TextFormat::AQUA,
        TextFormat::RED,
        TextFormat::LIGHT_PURPLE,
        TextFormat::YELLOW,
        TextFormat::WHITE
    ];

    /**
     * @param string $colorCode
     * @return array{0: int, 1: int, 2: int}
     */
    public static function textFormatToRGB(string $colorCode): array
    {
        return self::TEXTFORMAT_RGB[array_search($colorCode, self::TEXTFORMAT_LIST, true)];
    }

    /**
     * Returns whether the CompoundTag contains a child tag with the specified name.
     *
     * @phpstan-param class-string<Tag> $expectedClass
     */
    public static function hasTag(CompoundTag $tag, string $name, string $expectedClass = Tag::class): bool
    {
        assert(is_a($expectedClass, Tag::class, true));
        return $tag->getTag($name) instanceof $expectedClass;
    }

    /**
     * @param Player $player
     * @param bool $empty
     * @param Player[] $targets
     */
    public static function sendArmour(Player $player, bool $empty = false, array $targets = []): void
    {
        $head = $empty ? VanillaItems::AIR() : $player->getArmorInventory()->getHelmet();
        $chest = $empty ? VanillaItems::AIR() : $player->getArmorInventory()->getChestplate();
        $legs = $empty ? VanillaItems::AIR() : $player->getArmorInventory()->getLeggings();
        $feet = $empty ? VanillaItems::AIR() : $player->getArmorInventory()->getBoots();

        TypeConverter::broadcastByTypeConverter($targets, function (TypeConverter $typeConverter) use ($player, $head, $chest, $legs, $feet): array {
            return [MobArmorEquipmentPacket::create($player->getId(),
                ItemStackWrapper::legacy($typeConverter->coreItemStackToNet($head)),
                ItemStackWrapper::legacy($typeConverter->coreItemStackToNet($chest)),
                ItemStackWrapper::legacy($typeConverter->coreItemStackToNet($legs)),
                ItemStackWrapper::legacy($typeConverter->coreItemStackToNet($feet)),
                new ItemStackWrapper(0, ItemStack::null())
            )];
        });
    }

    public static function setUnbreakable(Item $item): Item
    {
        return $item instanceof Durable ? $item->setUnbreakable() : $item;
    }

    public static function getRomanNumber(int $number): string
    {
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $returnValue = '';

        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }

        return $returnValue;
    }

    public static function applyDye(Wool|StainedGlass|StainedHardenedClay $block, DyeColor $color): Block
    {
        $block->setColor($color);
        return $block;
    }

    /**
     * Returns whether the first item is better than the second item.
     */
    public static function getArmorDifference(Item $item, Item $item2): int
    {
        $firstItemDefensePoints = $item instanceof Armor ? $item->getDefensePoints() : 0;
        $secondItemDefensePoints = $item2 instanceof Armor ? $item2->getDefensePoints() : 0;

        return $firstItemDefensePoints - $secondItemDefensePoints;
    }

    /**
     * Converts a number (e.g. 5) to its word representation (e.g. "fifth").
     */
    public static function getWordFromNumber(int $number): string
    {
        return match ($number) {
            1 => "first",
            2 => "second",
            3 => "third",
            5 => "fifth",
            8 => "eighth",
            9 => "ninth",
            default => "{$number}th"
        };
    }

    public static function replaceItem(Player $player, Item $oldItem, Item $newItem): bool
    {
        /** @var Inventory[] $inventories */
        $inventories = [$player->getInventory(), $player->getOffHandInventory(), $player->getCursorInventory(), $player->getArmorInventory()];

        $checkTags = $oldItem->hasNamedTag();
        foreach ($inventories as $inventory) {
            foreach ($inventory->getContents() as $slot => $item) {
                if ($item->equals($oldItem, true, $checkTags)) {
                    $inventory->setItem($slot, $newItem);
                    return true;
                }
            }
        }

        return false;
    }
}