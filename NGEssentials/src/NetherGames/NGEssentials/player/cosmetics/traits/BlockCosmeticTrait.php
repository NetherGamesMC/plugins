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
use pocketmine\block\Block;
use pocketmine\item\ItemBlock;

/**
 * @mixin Cosmetic
 */
trait BlockCosmeticTrait
{
    use ItemCosmeticTrait {
        getItem as private;
        processItem as private;
        isItemCosmeticEntry as private;
    }

    /** @var array<string, Block> */
    private array $blockCache = [];

    protected function getBlock(CosmeticDataEntry $entry): Block
    {
        return $this->processBlock($entry, clone($this->blockCache[$entry->getHash()] ??= $this->constructBlock($entry)));
    }

    protected function processBlock(CosmeticDataEntry $entry, Block $block): Block
    {
        return $block;
    }

    private function constructBlock(CosmeticDataEntry $entry): Block
    {
        $item = $this->constructItem($entry);

        if ($item instanceof ItemBlock) {
            return $item->getBlock();
        }

        throw new InvalidArgumentException("Invalid block data for item $entry->id:");
    }

    protected function isBlockCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return self::isItemCosmeticEntry($entry) && $this->getItem($entry) instanceof ItemBlock;
    }
}