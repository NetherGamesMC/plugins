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

namespace bedwars\shops;

use bedwars\BWTeam;
use bedwars\shops\cost\CostType;
use bedwars\shops\cost\ItemCost;
use bedwars\utils\Utils;
use Closure;
use libminigames\utils\AutoUpgrader;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_key_last;
use function array_map;
use function is_array;

class ShopItem
{

    /**
     * @param Item|Item[] $value
     * @param (Closure(static, Player, BWTeam): ?string)|null $purchaseValidatorFn
     * @param (Closure(Item, Player, BWTeam): Item)|null $itemModifierFn
     * @param (Closure(static, Player, BWTeam): void)|null $onPrePurchase
     * @param (Closure(static, Player, BWTeam): void)|null $onPurchase
     * @param (Closure(Item, Player, BWTeam): void)|null $replaceFn
     */
    public function __construct(
        public readonly string            $name,
        public readonly Item|array        $value,
        public readonly string            $iconPath,
        public ItemCost                   $cost,
        public readonly string            $description = "",
        public readonly ?Closure          $purchaseValidatorFn = null,
        public readonly ?Closure          $itemModifierFn = null,
        public readonly ?Closure          $onPrePurchase = null,
        public readonly ?Closure          $onPurchase = null,
        public readonly ShopInventorySlot $slot = ShopInventorySlot::INVENTORY,
        public readonly bool              $overrideInventory = false,
        public ?Closure                   $replaceFn = null
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Item|Item[]
     */
    public function getValue(): Item|array
    {
        return is_array($this->value) ? array_map(fn(Item $item) => Utils::setUnbreakable(clone $item), $this->value) : Utils::setUnbreakable(clone $this->value);
    }

    /**
     * Returns the sum of the count of all items in the shop item
     */
    public function getCount(): int
    {
        return is_array($this->value) ? (int)array_sum(array_map(fn(Item $item) => $item->getCount(), $this->value)) : $this->value->getCount();
    }

    public function getDisplayItem(): Item
    {
        // TODO: this is a hack given that armor is the only array-styled item in the shop
        // there are a plethora of ways to improve this, but for now this will do
        return is_array($this->value) ? clone $this->value[array_key_last($this->value)] : clone $this->value;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCost(): ItemCost
    {
        return $this->cost;
    }

    /**
     * Returns true if the player has sufficient funds to purchase the item
     */
    public function canPurchase(Player $player, BWTeam $team, ?int $multiplier = null): bool
    {
        return $this->cost->contains($player, $team, $multiplier);
    }

    public function getTargetedInventory(Player $player): Inventory
    {
        return match ($this->slot) {
            ShopInventorySlot::INVENTORY => $player->getInventory(),
            ShopInventorySlot::ARMOR_INVENTORY => $player->getArmorInventory(),
            ShopInventorySlot::OFFHAND_INVENTORY => $player->getOffHandInventory()
        };
    }

    private function mapItemForPurchase(Item $item, Player $player, BWTeam $team, ?int $multiplier = null): Item
    {
        $instance = (clone $item)->setCount($item->getCount() * ($multiplier ?? 1));
        return $this->itemModifierFn !== null ? ($this->itemModifierFn)($instance, $player, $team) : $instance;
    }

    private function getDisplayName(?int $multiplier = null): string
    {
        return match (true) {
            is_array($this->value) => ($multiplier !== null && $multiplier !== 1 ? "{$multiplier}x" : "") . $this->name,
            default => ($multiplier !== null & $multiplier !== 1 ? ($multiplier * $this->value->getCount()) . "x" : "") . $this->value->getName()
        };
    }

    public function getPluralizedCostName(bool $plural = true): string
    {
        return $this->getCost()->getName() . ($plural && $this->getCost()->getType() === CostType::EMERALD() ? 's' : '');
    }

    /**
     * Handles the item-adding logic and calls the associated `onPurchase` closure
     * Returns an error message or null if the purchase was successful
     */
    public function handlePurchase(Player $player, BWTeam $team, ?int $multiplier = null): ?string
    {
        /** @var NGPlayer $player */
        if (!$this->canPurchase($player, $team, $multiplier)) {
            return TextFormat::RED . "You don't have enough {$this->getPluralizedCostName()}!";
        }

        if ($this->purchaseValidatorFn !== null && ($errorMessage = ($this->purchaseValidatorFn)($this, $player, $team)) !== null) {
            return $errorMessage;
        }

        $value = $this->getValue();
        $items = match (true) {
            is_array($value) => array_map(fn(Item $item) => $this->mapItemForPurchase($item, $player, $team, $multiplier), $value),
            default => [$this->mapItemForPurchase($value, $player, $team, $multiplier)]
        };

        $inventory = $this->getTargetedInventory($player);
        if (!$this->overrideInventory && !$inventory->canAddItem(...$items)) {
            return TextFormat::RED . "You don't have enough space in your inventory!";
        }

        if ($this->onPrePurchase !== null) {
            ($this->onPrePurchase)($this, $player, $team);
        }

        $cost = $this->cost->asItem($team);
        if ($multiplier !== null) {
            $cost->setCount($cost->getCount() * $multiplier);
        }

        $player->getInventory()->removeItem($cost);

        if ($this->overrideInventory) {
            foreach ($items as $slot => $item) {
                $inventory->setItem($slot, $item);
            }
        } else if ($this->replaceFn !== null) {
            foreach ($items as $item) {
                ($this->replaceFn)($item, $player, $team);
            }
        } else {
            $inventory->addItem(...array_map(fn(Item $item) => AutoUpgrader::getInstance()->upgradeItem($player, $item), $items));
        }

        $player->sendConditionalMessage(TextFormat::GREEN . "You purchased " . TextFormat::GOLD . $this->getDisplayName($multiplier));

        if ($this->onPurchase !== null) {
            ($this->onPurchase)($this, $player, $team);
        }

        return null;
    }
}