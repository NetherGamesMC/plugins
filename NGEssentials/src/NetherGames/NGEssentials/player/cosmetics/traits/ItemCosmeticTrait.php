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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\cosmetics\traits;

use InvalidArgumentException;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\NbtException;
use function json_encode;

/**
 * @mixin Cosmetic
 */
trait ItemCosmeticTrait
{
    /** @var array<string, Item> */
    private array $itemCache = [];

    private const ITEM_KEY = 'item';

    private const ITEM_ID_KEY = 'id';
    private const ITEM_NBT_KEY = 'nbt';

    protected function getItem(CosmeticDataEntry $entry): Item
    {
        return $this->processItem($entry, clone($this->itemCache[$entry->getHash()] ??= $this->constructItem($entry)));
    }

    private function constructItem(CosmeticDataEntry $entry): Item
    {
        $itemKey = $entry->data[self::ITEM_KEY];
        $itemIdKey = $itemKey[self::ITEM_ID_KEY] ?? throw new InvalidArgumentException("Missing item key for cosmetic $entry->id");
        $itemNbtKey = $itemKey[self::ITEM_NBT_KEY] ?? null;

        $item = StringToItemParser::getInstance()->parse($itemIdKey);

        try {
            if ($itemNbtKey !== null) {
                $item->setNamedTag(JsonNbtParser::parseJson(json_encode($itemNbtKey)));
            }
        } catch (NbtException $e) {
            throw new InvalidArgumentException("Invalid NBT data for item $entry->id: " . $e->getMessage());
        }

        return $item;
    }

    protected function processItem(CosmeticDataEntry $entry, Item $item): Item
    {
        return $item;
    }

    protected function isItemCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::ITEM_KEY]);
    }
}