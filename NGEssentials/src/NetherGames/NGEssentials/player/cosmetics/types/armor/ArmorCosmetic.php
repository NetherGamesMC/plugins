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

namespace NetherGames\NGEssentials\player\cosmetics\types\armor;

use InvalidArgumentException;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\traits\ItemCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use pocketmine\color\Color;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use function mt_rand;

abstract class ArmorCosmetic extends Cosmetic
{
    use ItemCosmeticTrait;

    public function __construct(private readonly int $slot, int $saveId, CosmeticHandler $handler)
    {
        parent::__construct($saveId, $handler);
    }

    public function onSelect(Player $player): bool
    {
        $this->equip($player);
        return false;
    }

    public function equip(Player $player): void
    {
        if (($entry = $this->getSelectedEntry($player)) === null) {
            $item = VanillaItems::AIR();
        } else {
            $item = $this->getItem($entry->getDataEntry());

            if ($item instanceof Armor) {
                if ($item->getArmorSlot() !== $this->slot) {
                    throw new InvalidArgumentException('Armor slot does not match');
                }
            }
        }

        $player->getArmorInventory()->setItem($this->slot, $item);
    }

    public function remove(Player $player): void
    {
        $player->getArmorInventory()->clear($this->slot);
    }

    protected function processItem(CosmeticDataEntry $entry, Item $item): Item
    {
        return match ($entry->id) {
            100 => $item instanceof Armor ? $item->setCustomColor(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))) : throw new InvalidArgumentException('Item is not an instance of Armor'),
            default => $item,
        };
    }

    public function onTick(Player $player): void
    {
        if ($this->getSelectedEntry($player)?->id === 100) { // 100 is the ID of the rainbow armor
            $this->equip($player);
        }
    }

    public function showSkin(): bool
    {
        return true;
    }
}